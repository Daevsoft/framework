<?php
class TestEvent extends Event
{
    public function __construct() {
    }
    public function do()
    {
        // DO THE EVENT
        echo 'Aww i\'m triggered!';
    }
}