<?php

namespace Ds\Foundations\Security;

use Ds\Foundations\Config\Env;
use Ds\Foundations\Network\Request;

class Csrf
{
  public static function verify($token)
  {
    return $_COOKIE['csrf_token'] == $token;
  }

  public static function token()
  {
    return $_COOKIE['csrf_token'];
  }
  public static function generate()
  {
    Request::trackRoute();
    $cookie_expired = Env::get('COOKIE_EXPIRED', 15) * 86400; //days
    $token = hash("md5", Env::get('SECRET_KEY') . time());
    setcookie('csrf_token', $token, $cookie_expired + time());
  }
}
