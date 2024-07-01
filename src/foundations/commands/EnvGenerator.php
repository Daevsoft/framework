<?php
namespace Ds\Foundations\Commands;

use Ds\Dir;
use Ds\Foundations\Config\AppEnv;
use Ds\Helper\Str;

class EnvGenerator extends Runner {
  private function getEnvFilename(): String {
    $filename = $this->options[0] ?? '';
    return Str::replace($filename, '.env', '');
  }
  function run(){
    $envFile = $this->getEnvFilename();

    $envFile = Dir::$MAIN.$envFile.'.env';
    AppEnv::create($envFile);
    Console::writeln('Config was updated!', Console::LIGHT_GREEN);
  }
}