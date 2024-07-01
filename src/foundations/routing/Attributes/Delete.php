<?php
namespace Ds\Foundations\Routing\Attributes;

use Attribute;
use Ds\Foundations\Routing\Route;

#[Attribute]
class Delete extends RouteRequestAttr{
  protected $requestMethod = 'delete';
  public function apply($controllerName, $methodName){
    $route = Route::delete($this->uri, [$controllerName, $methodName]);
    if($this->middlewares != null){
      $route->middleware($this->middlewares);
    }
  }
}