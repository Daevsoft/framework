<?php

declare(strict_types=1);

namespace Ds\Database;

use PDO;

abstract class Migration
{
    abstract public function up(PDO $connection): void;

    abstract public function down(PDO $connection): void;
}
