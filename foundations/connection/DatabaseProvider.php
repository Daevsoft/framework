<?php
namespace Ds\Foundations\Connection;

use Ds\Foundations\Provider;

class DatabaseProvider implements Provider{
  static Db $db;
  function install(){
    self::$db = new Db();
  }
  function run(){
    self::$db->getConnection();
  }
}