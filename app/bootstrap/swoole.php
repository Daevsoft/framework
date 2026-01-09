<?php

use Ds\Server\SwooleServer;

$server = new SwooleServer($app);

$server->start();
