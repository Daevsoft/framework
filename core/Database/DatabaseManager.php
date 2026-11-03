<?php

declare(strict_types=1);

namespace Ds\Database;

use PDO;
use RuntimeException;
use Throwable;

final class DatabaseManager
{
    private static array $channels = [];
    private static array $created = [];
    private static ?self $defaultManager = null;
    private static array $poolUsers = [];
    private static array $activeLeases = [];

    private ?PDO $localConnection = null;
    private bool $shutdown = false;

    public function __construct(private readonly array $config)
    {
        $key = $this->poolKey();
        self::$poolUsers[$key] = (self::$poolUsers[$key] ?? 0) + 1;
    }

    public static function default(): self
    {
        return self::$defaultManager ??= new self(config('database', []));
    }

    public static function setDefault(?self $manager): void
    {
        self::$defaultManager = $manager;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function schema(): SchemaBuilder
    {
        return new SchemaBuilder($this);
    }

    public function driver(): string
    {
        return (string) ($this->config['driver'] ?? 'mysql');
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->withConnection(static function (PDO $pdo) use ($sql, $bindings): array {
            $statement = $pdo->prepare($sql);
            try {
                $statement->execute($bindings);
                return $statement->fetchAll();
            } finally {
                $statement->closeCursor();
            }
        });
    }

    public function execute(string $sql, array $bindings = []): int
    {
        return $this->withConnection(static function (PDO $pdo) use ($sql, $bindings): int {
            $statement = $pdo->prepare($sql);
            try {
                $statement->execute($bindings);
                return $statement->rowCount();
            } finally {
                $statement->closeCursor();
            }
        });
    }

    public function transaction(callable $callback): mixed
    {
        return $this->withConnection(static function (PDO $pdo) use ($callback): mixed {
            $pdo->beginTransaction();
            try {
                $result = $callback($pdo);
                $pdo->commit();
                return $result;
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        });
    }

    public function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*\z/', $identifier) !== 1) {
            throw new \InvalidArgumentException("Unsafe SQL identifier [{$identifier}].");
        }

        $driver = (string) ($this->config['driver'] ?? 'mysql');
        if ($driver === 'sqlsrv') {
            return implode('.', array_map(
                static fn (string $part): string => '[' . $part . ']',
                explode('.', $identifier)
            ));
        }

        $quote = $driver === 'mysql' ? '`' : '"';
        return implode('.', array_map(
            static fn (string $part): string => $quote . $part . $quote,
            explode('.', $identifier)
        ));
    }

    public function shutdown(): void
    {
        if ($this->shutdown) {
            return;
        }

        $this->shutdown = true;
        $this->localConnection = null;

        if (self::$defaultManager === $this) {
            self::$defaultManager = null;
        }

        $key = $this->poolKey();
        $remainingUsers = max(0, (self::$poolUsers[$key] ?? 1) - 1);
        if ($remainingUsers > 0) {
            self::$poolUsers[$key] = $remainingUsers;
            return;
        }

        unset(self::$poolUsers[$key]);
        $this->closePoolIfUnused($key);
    }

    /**
     * Close a pool only after its final manager and in-flight lease are gone.
     * An active callback may still need to return its connection to the channel.
     */
    private function closePoolIfUnused(string $key): void
    {
        if ((self::$poolUsers[$key] ?? 0) > 0 || (self::$activeLeases[$key] ?? 0) > 0) {
            return;
        }

        $channel = self::$channels[$key] ?? null;
        try {
            if (is_object($channel) && method_exists($channel, 'close')) {
                $channel->close();
            }
        } catch (Throwable) {
            // Destructors and worker shutdown must tolerate runtime-closed channels.
        } finally {
            unset(self::$channels[$key], self::$created[$key], self::$activeLeases[$key]);
        }
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    private function __clone()
    {
    }

    private function withConnection(callable $callback): mixed
    {
        if ($this->shutdown) {
            throw new RuntimeException('DatabaseManager has been shut down and cannot be reused.');
        }

        if (
            !class_exists('Swoole\\Coroutine\\Channel')
            || !class_exists('Swoole\\Coroutine')
            || \Swoole\Coroutine::getCid() < 0
        ) {
            $this->localConnection ??= $this->createConnection();
            return $callback($this->localConnection);
        }

        $key = $this->poolKey();
        // Reserve the pool before any potentially blocking pop/create operation.
        // This prevents shutdown from closing the channel while a coroutine waits.
        self::$activeLeases[$key] = (self::$activeLeases[$key] ?? 0) + 1;

        try {
            $channelClass = 'Swoole\\\\Coroutine\\\\Channel';
            $max = max(1, (int) ($this->config['pool']['max'] ?? 10));
            $timeout = max(0.1, (float) ($this->config['pool']['timeout'] ?? 3.0));
            $channel = self::$channels[$key] ??= new $channelClass($max);

            if ($channel->length() > 0) {
                $connection = $channel->pop($timeout);
                if ($connection instanceof PDO) {
                    return $this->runAndRelease($connection, $callback, $channel, $key);
                }
            }

            $created = self::$created[$key] ?? 0;
            if ($created < $max) {
                self::$created[$key] = $created + 1;
                try {
                    $connection = $this->createConnection();
                } catch (Throwable $exception) {
                    self::$created[$key] = max(0, (self::$created[$key] ?? 1) - 1);
                    throw $exception;
                }

                return $this->runAndRelease($connection, $callback, $channel, $key);
            }

            $connection = $channel->pop($timeout);
            if (!$connection instanceof PDO) {
                throw new RuntimeException('Timed out waiting for a database connection from the Swoole pool.');
            }

            return $this->runAndRelease($connection, $callback, $channel, $key);
        } finally {
            $remainingLeases = max(0, (self::$activeLeases[$key] ?? 1) - 1);
            if ($remainingLeases > 0) {
                self::$activeLeases[$key] = $remainingLeases;
            } else {
                unset(self::$activeLeases[$key]);
            }

            $this->closePoolIfUnused($key);
        }
    }

    private function runAndRelease(PDO $connection, callable $callback, object $channel, string $key): mixed
    {
        try {
            return $callback($connection);
        } finally {
            if (!$channel->push($connection, 0.1)) {
                self::$created[$key] = max(0, (self::$created[$key] ?? 1) - 1);
                $connection = null;
            }
        }
    }

    private function createConnection(): PDO
    {
        $driver = (string) ($this->config['driver'] ?? 'mysql');
        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? 3306);
        $database = (string) ($this->config['database'] ?? '');
        $charset = (string) ($this->config['charset'] ?? 'utf8mb4');

        $dsn = match ($driver) {
            'mysql' => "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
            'pgsql' => "pgsql:host={$host};port={$port};dbname={$database}",
            'sqlsrv' => "sqlsrv:Server={$host},{$port};Database={$database}",
            'sqlite' => 'sqlite:' . (string) ($this->config['database'] ?? ':memory:'),
            default => throw new RuntimeException("Unsupported database driver [{$driver}]."),
        };

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];

        $sslCertificate = (string) ($this->config['ssl_cert'] ?? '');
        if ($driver === 'mysql' && $sslCertificate !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[constant('PDO::MYSQL_ATTR_SSL_CA')] = $sslCertificate;
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] =
                    (bool) ($this->config['ssl_verify'] ?? true);
            }
        }

        $pdo = new PDO(
            $dsn,
            (string) ($this->config['username'] ?? ''),
            (string) ($this->config['password'] ?? ''),
            $options
        );

        return $pdo;
    }

    private function poolKey(): string
    {
        return hash('sha256', serialize([
            $this->config['driver'] ?? 'mysql',
            $this->config['host'] ?? null,
            $this->config['port'] ?? null,
            $this->config['database'] ?? null,
            $this->config['username'] ?? null,
            hash('sha256', (string) ($this->config['password'] ?? '')),
            $this->config['charset'] ?? null,
            $this->config['ssl_cert'] ?? null,
            $this->config['ssl_verify'] ?? true,
            $this->config['pool']['max'] ?? 10,
        ]));
    }
}
