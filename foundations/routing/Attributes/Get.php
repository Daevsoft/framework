<?php
namespace Ds\Foundations\Routing\Attributes;

use Attribute;
use Ds\Foundations\Routing\Route;

#[Attribute]
class Get extends RouteRequestAttr{
  protected $requestMethod = 'get';
  public function apply($controllerName, $methodName){
    Route::get($this->uri, [$controllerName, $methodName]);
  }
}