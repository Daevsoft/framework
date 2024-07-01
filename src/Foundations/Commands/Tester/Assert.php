<?php
namespace Ds\Foundations\Commands\Tester;

use Ds\Foundations\Commands\Console;

class AssertResult {
  private bool $condition;
  public function printResult(){
    if($this->condition){
      Console::write("PASS ", Console::LIGHT_GREEN);
    }else{
      Console::write("FAIL ", Console::LIGHT_RED);
    }
  }
  public function __construct(bool $condition) {
    $this->condition = $condition;
  }
  public function commit(){
    if($this->condition){
      Tester::passIncrease();
    }else{
      Tester::failIncrease();
    }
  }
}

class Assert {
  public static function check($condition){
    return (new AssertResult($condition));
  }
  public static function equal(mixed $value1, mixed $value2){
    return self::check($value1 == $value2);
  }
  public static function notEqual(mixed $value1, mixed $value2){
    return self::check($value1 != $value2);
  }
  public static function greaterThan(mixed $value1, mixed $value2){
    return self::check($value1 > $value2);
  }
  public static function lowerThan(mixed $value1, mixed $value2){
    return self::check($value1 < $value2);
  }
}