<?php

use Ds\Container\Container;
use Ds\Server\SwooleServer;

// Resolve container/app from global scope if present or boot a minimal server
$container = new Container();
$host = $_ENV['APP_HOST'] ?? '127.0.0.1';
$port = (int) ($_ENV['APP_PORT'] ?? 8080);

$server = new SwooleServer($container, $host, $port);
$server->start();
