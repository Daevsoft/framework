<?php
namespace Ds\Foundations\Session;

class Session
{
    private static $user;
    public static function user()
    {
        $user = session('user');
        if ($user !== null) {
            if (self::$user !== null) {
                return self::$user;
            }
            if (is_string($user) && self::looksLikeSerialized($user)) {
                $maybe = @unserialize($user);
                if ($maybe !== false || $user === 'b:0;') {
                    self::$user = $maybe;
                } else {
                    self::$user = $user;
                }
            } else {
                self::$user = $user;
            }
            return self::$user;
        }
        return null;
    }
    public static function destroy()
    {
        \Ds\Foundations\Session\SessionManager::init()->destroy();
    }

    private static function looksLikeSerialized($str)
    {
        if (! is_string($str)) {
            return false;
        }

        $str = trim($str);
        if ($str === 'N;') {
            return true;
        }

        if (strlen($str) < 4) {
            return false;
        }

        if ($str[1] !== ':') {
            return false;
        }

        $last = substr($str, -1);
        if ($last !== ';' && $last !== '}') {
            return false;
        }

        return in_array($str[0], ['s', 'a', 'O', 'b', 'i', 'd', 'N']);
    }
}
