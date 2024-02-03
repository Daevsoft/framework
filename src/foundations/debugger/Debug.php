<?php
namespace Ds\Foundations\Debugger;

use DebugBar\StandardDebugBar;
use Exception;

class Debug {
  private static StandardDebugBar|null $debugger;
  private static $debugbarRenderer;
  private static $isDebug;
  public static function disabled(){
    self::$isDebug = false;
  }
  private static function standBy(){
    if(self::$debugger == null){
      self::$debugger = new StandardDebugBar();
    }
    if(self::$debugbarRenderer == null){
      self::$debugbarRenderer = self::$debugger->getJavascriptRenderer();
    }
  }
  public static function init(){
    self::$debugger = null;
    self::$debugbarRenderer = null;
    self::$isDebug = true;
  }
  public static function log($args){
    if(self::$isDebug){
      self::standBy();
      self::$debugger['messages']->addMessage($args);
    }
  }
  public static function error(Exception $e){
    if(self::$isDebug){
      self::standBy();
      self::$debugger['exceptions']->addException($e);
    }
  }
  public static function writeLog(){
    if(self::$isDebug){
      self::standBy();
      echo self::$debugbarRenderer->renderHead();
      echo self::$debugbarRenderer->render();
    }
  }
}