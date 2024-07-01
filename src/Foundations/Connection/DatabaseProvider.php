<?php
namespace Ds\Foundations\Connection;

use Ds\Foundations\Provider;

class DatabaseProvider implements Provider{
  static Db $db;
  static bool $isInit = false;
  function install(){
    self::$db = new Db();
  }
  function run(){
  }
  public static function getConnection(){
    if(!self::$isInit){
      self::$isInit = true;
      self::$db->init();
    }

    return self::$db;
  }
}