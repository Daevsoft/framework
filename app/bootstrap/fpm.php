<?php

use Ds\Http\Kernel;

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    Ds\Http\Request::capture()
);

$response->send();
