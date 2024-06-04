<?php
namespace Ds\Foundations\Commands\Migrate;

interface Migrations
{
  public function up(Scheme $scheme);
  public function down(Scheme $scheme);
}