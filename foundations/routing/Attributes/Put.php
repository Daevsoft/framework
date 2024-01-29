<?php
namespace Ds\Foundations\Routing\Attributes;

use Attribute;
use Ds\Foundations\Routing\Route;

#[Attribute]
class Put extends RouteRequestAttr{
  protected $requestMethod = 'put';
  public function apply($controllerName, $methodName){
    Route::put($this->uri, [$controllerName, $methodName]);
  }
}