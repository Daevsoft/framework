<?php
namespace Ds\Foundations\Routing\Attributes;

use Attribute;
use Ds\Foundations\Routing\Route;

#[Attribute]
class Post extends RouteRequestAttr{
  protected $requestMethod = 'post';
  public function apply($controllerName, $methodName){
    $route = Route::post($this->uri, [$controllerName, $methodName]);
    if($this->middlewares != null){
      $route->middleware($this->middlewares);
    }
  }
}