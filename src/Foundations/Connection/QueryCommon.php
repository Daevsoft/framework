<?php

namespace Ds\Foundations\Connection;

use Ds\Foundations\Config\Env;
use Ds\Foundations\Exceptions\dsException;
use Ds\Helper\Str;
use Exception;
use PDO;

define('SQLSERV', 'sqlserv');
define('MYSQL', 'mysql');
define('POSTGRE', 'pgsql');
define('SQLITE', 'sqlite');
define('SPACE', ' ');

trait QueryCommon
{
    private $driver;
    private $host;
    private $username;
    private $password;
    private $database;
    private $ssl_cert;
    private $ssl_verify;
    public const SELECT = 'SELECT';
    public const DISTINCT = 'DISTINCT';
    public const BULK_INSERT = 'BULK_INSERT';
    public const INSERT = 'INSERT';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';

    public const ASC = 'ASC';
    public const DESC = 'DESC';

    /**
     * orderAdditional
     *
     * @var string
     */
    protected $orderAdditional;
    /**
     * limitAdditional
     *
     * @var string
     */
    protected $limitAdditional;
    /**
     * groupAdditional
     *
     * @var string
     */
    protected $groupAdditional;
    /**
     * havingAdditional
     *
     * @var string
     */
    protected $havingAdditional;
    /**
     * quotSql
     *
     * @var string
     */
    protected $quotSql;
    /**
     * endQuotSql
     *
     * @var char
     */
    protected $endQuotSql;
    /**
     * bindSymbol
     *
     * @var char
     */
    protected $bindSymbol;

    /**
     * FixQuot
     *
     * @param  string $text
     * @return string
     */
    private function FixQuot($text)
    {
        $text = trim($text);
        if ($text == "*" || $text[0] == $this->quotSql) {
            return $text;
        }
        if (empty($text)) return $text;

        return $this->quotSql . $text . $this->endQuotSql;
    }
    /**
     * WrapQuot
     *
     * @param  string $name
     * @param  bool $reverseQuot
     * @return string
     */
    public function WrapQuot($name, $reverseQuot = false)
    {
        $name = trim($name);
        if (empty($name)) return $name;
        $isRaw = $this->checkRaw($name);
        if ($isRaw) return $name;

        $isFunction = preg_match('/.*[(].*[)]/', $name);
        if ($isFunction) {
            $funcName = substr($name, 0, strpos($name, '('));
            $wrapStart = strpos($name, '(');
            $param = substr($name, $wrapStart + 1, strrpos($name, ')') - 4);
            return $funcName . '(' . $this->WrapQuot($param) . ')' . substr($name, strrpos($name, ')') + 1);
        }
        if (Str::contains($name, '.')) {
            $columnName = substr($name, strpos($name, '.') + 1);
            $columnAlias = array_map(function ($a) {
                return $this->FixQuot($a);
            }, explode(' ', $columnName));
            return substr($name, 0, strpos($name, '.') + 1) . implode(' ', $columnAlias);
        }

        if (Str::contains($name, ' ')) {
            $spaceIdx = strrpos($name, ' ');
            $selected = substr($name, 0, $spaceIdx);
            $alias = substr($name, $spaceIdx + 1);
            return $this->FixQuot($selected) . ' ' . $this->FixQuot($alias);
        }
        $isFunction = preg_match('/.*[(].*[)]/', $name);
        if (!$reverseQuot && $isFunction == 1)
            return $this->FixQuot($name);
        if ($name[0] == $this->quotSql) {
            return $name;
        } else
            return $this->FixQuot($name);
    }
    /**
     * GetType get parameter type of binding params
     *
     * @param  mixed $value
     * @return int
     */
    protected function GetType($value)
    {
        if (is_string($value)) {
            return PDO::PARAM_STR;
        } else if (is_numeric($value)) {
            return PDO::PARAM_INT;
        } else if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        return PDO::PARAM_STR;
    }
    protected function assignBindSymbol($paramName)
    {
        if (empty($paramName)) return $paramName;
        if ($paramName[0] == $this->bindSymbol) return $paramName;
        else return $this->bindSymbol . trim($paramName);
    }
    protected function setBehavior($startQuot, $endQuot, $bindSymbol = ':')
    {
        $this->quotSql = $startQuot;
        $this->endQuotSql = $endQuot;
        $this->bindSymbol = $bindSymbol;
    }
    /**
     * If value of query contains !! symbol on first word
     * it will remain as raw query
     *
     * @param  mixed $value
     * @return void
     */
    protected function checkRaw(&$value): bool
    {
        if (!is_string($value))
            return false;

        if (empty($value)) {
            return false;
        }
        if ($value[0] == '!' && $value[1] == '!') {
            $value = substr($value, 2);
            return true;
        }
        return false;
    }
    /**
     * @param  string $provider
     * @return void
     */
    public function setupProvider()
    {
        $this->driver = Env::get('DB_DRIVER');
        $this->host = Env::get('DB_HOST');
        $this->username = Env::get('DB_USERNAME');
        $this->password = Env::get('DB_PASSWORD');
        $this->database = Env::get('DB_NAME');
        $this->ssl_cert = Env::get('SSL_CERT');
        $this->ssl_verify = Env::get('SSL_VERIFY', false);

        try {
            if (empty($this->database)) {
                throw new dsException('Database not found!');
            }
            if (
                $this->driver == MYSQL ||
                $this->driver == POSTGRE ||
                $this->driver == SQLSERV
            ) {
                $this->setup();
            } else {
                throw new Exception("Provider not supported");
            }
        } catch (dsException $th) {
            //throw $th;
        }
    }
    private function setup()
    {
        switch ($this->driver) {
                // MySql Provider
            case MYSQL:
                $this->setBehavior('`', '`');
                break;
                // SQL Server Provider
            case SQLSERV:
                $this->setBehavior('[', ']');
                break;
        }
    }
}
