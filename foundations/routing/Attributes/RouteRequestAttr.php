<?php
namespace Ds\Foundations\Routing\Attributes;

use Attribute;
use Ds\Foundations\Routing\Route;

#[Attribute]
abstract class RouteRequestAttr {
  protected $uri;
  protected $requestMethod;
  public function __construct($uri) {
    $this->uri = $uri;
  }
  public function apply($controllerName, $methodName){}
}