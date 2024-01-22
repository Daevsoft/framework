<?php

namespace Ds\Foundations\Commands\Generator;

use Ds\Dir;
use Ds\Foundations\Commands\Console;
use Ds\Helper\Str;
use Ds\Foundations\Commands\Runner;
use Ds\Foundations\Common\File;

// command "add:* args"
class AddFile extends Runner
{
  public function run()
  {
    $type = '';
    if(count($this->options) > 0){
      $type = strtolower($this->options[0]);
      array_shift($this->options);
    }

    switch ($type) {
      case 'model':
        $this->createModel();
        break;
      case 'controller':
        $this->createController();
        break;

      case 'view':
        $this->createView();
        break;
  
      default:
        Console::writeln('Oops..', Console::RED);
        Console::write('do you mean, add:model, add:controller, or add:view ?', Console::DARK_GRAY);
        break;
    }
  }
  private function createView()
  {
    $_files = $this->options;
    foreach ($_files as $file) {
        $_filenames  = trim($file);
        $source = file_get_contents(__DIR__.'/template/view.empty');

        $source = Str::replace($source, [
            '{filename}' => $file
        ]);

        if(strstr($file, '.pie') == STRING_EMPTY){
            $_filenames .= '.pie';
        }
        $this->createFile($_filenames, 'views', $source);
    }
  }
  private function createController()
  {
    $_files = $this->options;
    foreach ($_files as $file) {
      $_filenames  = ucfirst(trim($file));
      if(!Str::contains(strtolower($_filenames), 'controller')){
        $_filenames .= 'Controller';
      }else{
        $_filenames = Str::replace($_filenames, 'controller', 'Controller');
      }
      $source = file_get_contents(__DIR__ . '/template/controller.empty');
      $source = Str::replace($source, [
        '{ControllerName}' => $_filenames,
        '{ViewName}' => strtolower(Str::replace($_filenames, 'controller')),
      ]);
      $_filenames = ucfirst($_filenames);
      $this->createFile($_filenames, 'controllers', $source);
    }
  }
  private function createModel()
  {
    $_files = $this->options;
    foreach ($_files as $file) {
      $filename  = ucfirst(trim($file));
      $source = file_get_contents(__DIR__ . '/template/model.empty');
      $source = Str::replace($source, [
        '{ModelName}' => $filename,
        '{tableName}' => strtolower($filename)
      ]);
      $filename = ucfirst($filename);
      $this->createFile($filename, 'models', $source);
    }
  }

  private function createFile($filename, $folder, $source)
  {
    $create = true;
    $dir = Dir::$APP . $folder;
    if(!is_dir($dir)){
      mkdir($dir, 7777, true);
    }
    $dir_filename = $dir . '/' . $filename . '.php';
    if (file_exists($dir_filename)) {
      Console::writeln('File ' . $filename . ' was exist.');
      $read = readline('Replace it (y/N)? ');
      if ($read == 'y') {
        $this->deleteFile($filename, $folder, STRING_EMPTY, FALSE);
      } else {
        $create = false;
      }
    }
    // Generate file decission
    if ($create) {
      (new File($dir_filename))->create($source)->close();
      Console::writeln($filename . ' successfully created!' . "\n", Console::LIGHT_GREEN);
    } else {
      Console::writeln('Skipped ' . $filename . ' ' . '!' . "\n", Console::DARK_GRAY);
    }
  }
  private function deleteFile($_files, $folder, $_restore = FALSE)
  {
    if (is_array($_files)) {
      foreach ($_files as $file) {
        $_filenames  = ucfirst(trim($file)) . '.php';
        $this->removeFileTemporary($_filenames, $folder, $_restore);
      }
    } else {
      $_filenames  = ucfirst(strtolower($_files)) . '.php';
      $this->removeFileTemporary($_filenames, $folder, $_restore);
    }
  }
  private function removeFileTemporary($_filenames, $_target_directory, $_restore)
  {
    $filenames = '/' . $_filenames;
    $new_directory = Dir::$TRASH . $_target_directory . $filenames;
    $directory = Dir::$APP . $_target_directory . $filenames;
    if (!$_restore) {
      rename($directory, $new_directory);
      Console::writeln($_filenames . ' has been removed!', Console::LIGHT_MAGENTA);
    } else {
      if (!file_exists($new_directory)) {
        Console::writeln($_filenames . ' not available on storage.');
      } else {
        rename($new_directory, $directory);
        Console::writeln($_filenames . ' restored!', Console::LIGHT_GREEN);
      }
    }
  }
}
