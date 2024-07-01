<?php
namespace Ds\Foundations;

interface Provider {
    function install();
    function run();
}