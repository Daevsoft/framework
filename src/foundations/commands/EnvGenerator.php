<?php
namespace Ds\Foundations\Commands;

use Ds\Dir;
use Ds\Foundations\Config\AppEnv;

class EnvGenerator extends Runner {
  function run(){
    $envFile = Dir::$MAIN.'.env';
    AppEnv::create($envFile);
    Console::writeln('Config was updated!', Console::LIGHT_GREEN);
  }
}