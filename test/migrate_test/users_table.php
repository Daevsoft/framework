<?php

use Ds\Foundations\Commands\Migrate\Migrations;
use Ds\Foundations\Commands\Migrate\Scheme;

class Users implements Migrations{
  function up(Scheme $scheme){

  }
  function down(Scheme $scheme) {
    $scheme->dropTable('users');
  }
}