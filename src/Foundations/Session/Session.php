<?php

namespace Ds\Foundations\Session;

class Session
{
  private static $user;
  public static function user()
  {
    $user = session('user');
    if ($user != null) {
      if (self::$user != null) {
        return self::$user;
      }
      self::$user = unserialize($user);
      return self::$user;
    }
    return null;
  }
  public static function destroy()
  {
    session_destroy();
  }
}
