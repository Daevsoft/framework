<?php
namespace Ds\Foundations\Session;

class SessionManager
{
    private static $instance;
    private $isSwoole;
    private $id;
    private $data = [];
    private $savePath;

    public static function init()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->isSwoole = (defined('SWOOLE_VERSION') || extension_loaded('swoole'));
        $projectRoot    = dirname(__DIR__, 6);
        $this->savePath = $projectRoot . '/storage/cache/sessions';
        if (! is_dir($this->savePath)) {
            @mkdir($this->savePath, 0777, true);
        }

        if ($this->isSwoole) {
            $this->startFileSession();
        } else {
            $this->startPhpSession();
        }
    }

    private function startPhpSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->data = &$_SESSION;
        $this->id   = session_id();
    }

    private function startFileSession()
    {
        $cookieName = 'DSF_SESSID';
        if (isset($_COOKIE[$cookieName]) && preg_match('/^[a-zA-Z0-9_\-]+$/', $_COOKIE[$cookieName])) {
            $this->id = $_COOKIE[$cookieName];
        } else {
            try {
                $this->id = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $this->id = uniqid('', true);
            }
            if (! headers_sent()) {
                setcookie($cookieName, $this->id, 0, '/');
            }
            $_COOKIE[$cookieName] = $this->id;
        }

        $file = $this->savePath . '/' . $this->id . '.sess';
        if (file_exists($file)) {
            $raw        = file_get_contents($file);
            $this->data = @unserialize($raw);
            if (! is_array($this->data)) {
                $this->data = [];
            }
        } else {
            $this->data = [];
            file_put_contents($file, serialize($this->data));
        }
    }

    private function save()
    {
        if ($this->isSwoole) {
            $file = $this->savePath . '/' . $this->id . '.sess';
            @file_put_contents($file, serialize($this->data));
        } else {
            // native PHP session uses $_SESSION which PHP persists automatically
        }
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function put($key, $value)
    {
        // For Swoole file-backed sessions we must persist values; store raw value so callers
        // (and existing code) continue to receive the same type they put in.
        $this->data[$key] = $value;
        if (! $this->isSwoole) {
            $_SESSION[$key] = $value;
        }
        $this->save();
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function delete($key)
    {
        unset($this->data[$key]);
        if (! $this->isSwoole) {
            unset($_SESSION[$key]);
        }
        $this->save();
    }

    public function destroy()
    {
        $this->data = [];
        if ($this->isSwoole) {
            $file = $this->savePath . '/' . $this->id . '.sess';
            if (file_exists($file)) {
                @unlink($file);
            }
        } else {
            $_SESSION = [];
            @session_destroy();
        }
    }

    public function id()
    {
        return $this->id;
    }
}
