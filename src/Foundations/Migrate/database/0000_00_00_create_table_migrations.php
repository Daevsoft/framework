<?php

use Ds\Foundations\Migrate\Column;
use Ds\Foundations\Migrate\Migrations;
use Ds\Foundations\Migrate\Scheme;

return new class implements Migrations
{
    public function up(Scheme $scheme)
    {
        $scheme->createTable('migrations',
            function (Column $column) {
                $column
                    ->id()
                    ->string('filename')->notNull()
                    ->text('raw')->notNull()
                    ->string('batch')->notNull()
                    ->createdAt();
            }
        );
    }
    public function down(Scheme $scheme)
    {
        // $scheme->dropIfExist('migrations');
    }
};
