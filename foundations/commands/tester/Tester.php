<?php
namespace Ds\Foundations\Commands\Tester;

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Commands\Runner;

class Tester extends Runner {
  private static $passTotal = 0;
  private static $failTotal = 0;
  public static function passIncrease(){
    self::$passTotal++;
  }
  public static function failIncrease(){
    self::$failTotal++;
  }
  public function run()
  {
    $length = count($this->options);
    if($length == 0){
      foreach (glob(ROOT.'tests\\*.spec.php') as $filename) {
        $clearFilename = substr($filename, strrpos($filename,'\\') + 1);
        Console::writeln($clearFilename, Console::BLUE);
        $output = [];
        exec(ROOT.'/vendor/bin/phpunit '.$filename, $output);
        Console::writeln($output[4], Console::GREEN);
  
        $lenOutput = count($output);
        for ($i=6; $i < $lenOutput - 1; $i++) { 
          Console::writeln($output[$i]);
        }
        Console::writeln($output[$lenOutput - 1], Console::LIGHT_YELLOW);
      }
    }else{
      $start = microtime(true);
      if($this->options[0] == '--unit'){
        Console::writeln('-------------------------------');
        foreach (glob(ROOT.'tests\\unit\\*.spec.php') as $filename) {
          $clearFilename = substr($filename, strrpos($filename,'\\') + 1);
          Console::writeln("[$clearFilename] ", Console::BLUE);
          require_once $filename;
          Console::writeln('-------------------------------');
        }
        $end = microtime(true) - $start;
        Console::write((self::$passTotal + self::$failTotal) . ' Test Completed! (', Console::LIGHT_YELLOW);
        Console::write(self::$passTotal .' PASS', Console::LIGHT_GREEN);
        Console::write(', ', Console::LIGHT_YELLOW);
        Console::write(self::$failTotal .' FAIL', Console::RED);
        Console::write(') ', Console::LIGHT_YELLOW);
        Console::write(number_format($end, 4, '.', '').'s', Console::DARK_GRAY);
      }
    }
  }
}