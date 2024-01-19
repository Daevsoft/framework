<?php

namespace Ds\Foundations\Network;

class Response
{
    public $isValid;
    public Request $request;
    public function __construct(bool $isValid = true, Request $request = null)
    {
        $this->isValid = $isValid;
        $this->request = $request ?? new Request();
    }
}
