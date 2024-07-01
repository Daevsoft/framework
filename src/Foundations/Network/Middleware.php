<?php

namespace Ds\Foundations\Network;

interface Middleware
{
    function handle(Request $request, $next): Response|null;
}
