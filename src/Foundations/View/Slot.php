<?php
namespace Ds\Foundation\View;

use Closure;

class Slot
{
    private $slots = [];

    public function __construct(array $slots = [])
    {
        $this->slots = $slots;
    }

    public function attachSlot($slotKey, Closure $content)
    {
        Slot::$slots[$slotKey] = $content;
    }
    public function getSlot($slotKey)
    {
        if (!isset($this->slots[$slotKey])) {
            return Slot::$slots[$slotKey]();
        }
        echo 'Slot ' . $slotKey . ' not found';
    }
}
