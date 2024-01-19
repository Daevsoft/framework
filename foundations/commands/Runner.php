<?php

namespace Ds\Foundations\Commands;

abstract class Runner
{
    protected $options = [];
    public function __construct($options = [])
    {
        $this->options = $options;
    }
    function run(){}
}
