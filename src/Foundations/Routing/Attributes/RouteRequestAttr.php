<?php
namespace Ds\Foundations\Routing\Attributes;

use Attribute;
use Ds\Foundations\Network\Middleware;
use Ds\Foundations\Routing\Route;

#[Attribute]
abstract class RouteRequestAttr {
  protected $uri;
  protected $requestMethod;
  protected $middlewares;
  public function __construct($uri, $middlewares = null) {
    if($uri == '/'){
      $uri = '';
    }
    if($middlewares != null){
      $this->middlewares = $middlewares;
    }
    $this->uri = $uri;
  }
  public function apply($controllerName, $methodName){}
}