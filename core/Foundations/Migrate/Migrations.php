<?php

declare(strict_types=1);

namespace Ds\Foundations\Migrate;

interface Migrations
{
    public function up(Scheme $scheme);
    public function down(Scheme $scheme);
}
