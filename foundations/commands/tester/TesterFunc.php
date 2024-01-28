<?php

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Provider;

function describe($name, $callback){
  $time = microtime(true);
  $test = $callback();
  $execTime = microtime(true) - $time;
  $test->commit();
  $test->printResult();
  Console::write('> '.$name . "\t");
  Console::writeln("\t".number_format($execTime, 4,'.','') . 's', Console::DARK_GRAY);
}

function mock(string $providerClass){
  (new $providerClass)->install();
}