<?php
namespace Ds\Foundations\Commands\CronJob;

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Commands\Runner;

class Job {
  protected function start(){
  }
  public function run(){
    $time = microtime(true);
    $this->start();
    $execTime = microtime(true) - $time;
    Console::writeln("\tCompleted in ".number_format($execTime, 4,'.','') . 's ', Console::DARK_GRAY);
  }
}