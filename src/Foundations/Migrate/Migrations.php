<?php
namespace Ds\Foundations\Migrate;


interface Migrations
{
  public function up(Scheme $scheme);
  public function down(Scheme $scheme);
}