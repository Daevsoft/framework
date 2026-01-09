<?php

use Ds\App\Application;

$app = new Application;

$app->bootOnce();

if ($app->isSwoole()) {
    require __DIR__ . '/swoole.php';
} else {
    require __DIR__ . '/fpm.php';
}
