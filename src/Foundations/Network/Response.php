<?php
namespace Ds\Foundations\Network;

class Response
{
    public $isValid;
    public ?Request $request;
    public function __construct(bool $isValid = true, ?Request $request = null)
    {
        $this->isValid = $isValid;
        if ($request != null) {
            $this->request = $request;
        }

    }

    public static array $headers = [];
    public static int $status    = 200;

    public static function header(string $string, bool $replace = true, ?int $code = null)
    {
        if ($code !== null) {
            self::$status = $code;
        }

        [$key, $value]             = explode(':', $string, 2);
        self::$headers[trim($key)] = trim($value);
    }

    public static function applyToSwoole($response)
    {
        $response->status(self::$status);

        foreach (self::$headers as $k => $v) {
            $response->header($k, $v);
        }
        self::reset();
    }
    public static function applyToPhp()
    {
        http_response_code(self::$status);

        foreach (self::$headers as $k => $v) {
            header("$k: $v");
        }
        self::reset();
    }
    public static function reset()
    {
        self::$headers = [];
        self::$status  = 200;
    }
}
