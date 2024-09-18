<?php

namespace Ds\Foundations\Network;

abstract class Middleware
{
    public $options;
    function handle(Request $request, $next): Response | null
    {
        return $next();
    }
}
