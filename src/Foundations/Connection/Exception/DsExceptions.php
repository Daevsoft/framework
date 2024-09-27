<?php

namespace Ds\Foundations\Connection\Exception;

use Exception;
use Spatie\ErrorSolutions\Contracts\ProvidesSolution;
use Spatie\ErrorSolutions\Contracts\Solution;
use Throwable;

class DsExceptions extends Exception implements ProvidesSolution
{
    private DsSolutions $solution;
    public function __construct(string $message = "", $code = 0, Throwable $previous = null, DsSolutions $solution = null)
    {
        parent::__construct($message, (int) $code, $previous);
        $this->solution = $solution;
    }
    public function getSolution(): Solution
    {
        return $this->solution;
    }
}
