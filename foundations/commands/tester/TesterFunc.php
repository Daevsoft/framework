<?php

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Provider;

function describe($name, $callback){
  $time = microtime(true);
  $test = $callback();
  $execTime = microtime(true) - $time;
  $test->commit();
  Console::write("| ".number_format($execTime, 4,'.','') . 's ', Console::DARK_GRAY);
  $test->printResult();
  Console::writeln('> '.$name . "\t");
}

function mock(string $providerClass){
  (new $providerClass)->install();
}