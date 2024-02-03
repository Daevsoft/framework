<?php
namespace Ds\Foundation\View;

class Slot {
  public static $slots = [];
  public static function attachSlot($slotName, $content){
    Slot::$slots[$slotName] = $content;
  }
  public static function getSlot($slotName){
    return Slot::$slots[$slotName] ?? 'Slot '. $slotName . ' not found';
  }
}