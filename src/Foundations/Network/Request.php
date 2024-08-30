<?php

namespace Ds\Foundations\Network;

abstract class RequestAbstract
{
    protected function rules()
    {
        return null;
    }
    public function validated($rule = null)
    {
        $rule = $rule ?? $this->rules();
        if ($rule == null) {
            return true;
        }
        // do validation
        Validator::make($this->all(), $rule);
    }
    public function all()
    {
        return [];
    }
}

// #[AllowDynamicProperties]
class Request extends RequestAbstract
{
    public function json()
    {
        return json_decode(file_get_contents('php://input'));
    }
    public function all()
    {
        return $_REQUEST;
    }
    public function __get($name)
    {
        switch ($name) {
            case 'headers':
                return getallheaders();
            default:
                return $_REQUEST[$name] ?? null;
        }
    }
    public function add($propName, $value)
    {
        $this->{$propName} = $value;
    }
}
