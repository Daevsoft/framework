<?php
namespace Ds\Foundations\Commands\Migrate;

abstract class SqlTexter
{
  protected $current = '';
  protected function _add($text)
  {
    $this->current .= $text;
  }
  public function getRaw()
  {
    $current = $this->current;
    $this->current = '';
    return $current;
  }
}