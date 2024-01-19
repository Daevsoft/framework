<?php

namespace Ds\Foundations\Network;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class Request
{
    public function __construct()
    {
    }
    public function json()
    {
        return json_decode(file_get_contents('php://input'));
    }
    public function all()
    {
        return (array)$this->json();
    }
    public function __get($name)
    {
        switch ($name) {
            case 'headers':
                return getallheaders();
            default:
                return $_REQUEST[$name];
        }
    }
    public function add($propName, $value){
        $this->{$propName} = $value;
    }
}
