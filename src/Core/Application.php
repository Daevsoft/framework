<?php
namespace Ds\Core;

abstract class Application
{
    protected $middlewareAlias = [];
    public function boot()
    {}
    public function shutdown()
    {}
}
