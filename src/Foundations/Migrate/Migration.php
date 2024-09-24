<?php

namespace Ds\Foundations\Migrate;

use Ds\Dir;
use Ds\Foundations\Commands\Console;
use Ds\Foundations\Commands\Runner;
use Ds\Foundations\Exceptions\dsException;

class Migration  extends Runner
{
  public function run()
  {
    $opt = $this->options[0] ?? 'up';
    $scanMigrations = scandir(Dir::$MIGRATIONS);
    $this->migrations($scanMigrations, $opt);
  }
  private function migrations($filenames, $option)
  {
    $scheme = new Scheme();
    foreach ($filenames as $migrationFile) {
      if ($this->validateFile($migrationFile)) {
        Console::write($migrationFile);
        $migration = require_once Dir::$MIGRATIONS . $migrationFile;
        $this->doMigration($scheme, $migration, $option);
      }
    }
  }
  private function doMigration($scheme, $migration, $option)
  {
    try {
      if ($option == 'up') {
        $migration->up($scheme);
        Console::writeln(' Up', Console::GREEN);
      } else {
        $migration->down($scheme);
        Console::writeln(' Down', Console::DARK_GRAY);
      }
    } catch (\Throwable $th) {
      Console::write("\n | " . $th->getFile() . '(' . $th->getLine() . ')', Console::DARK_GRAY);
      Console::writeln("\n | ERROR : " . $th->getMessage(), Console::RED);
    }
  }
  private function validateFile($filename): bool
  {
    $ext = substr($filename, strpos($filename, '.php'));
    return $ext == '.php';
  }
}
