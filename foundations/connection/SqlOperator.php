<?php

namespace Ds\Foundations\Connection;

use Ds\Foundations\Exceptions\dsException;
use Exception;

class SqlOperator
{
    public const OR = 'OR';
    public const AND = 'AND';
    public const JOIN = 'JOIN';
    public const LEFT = 'LEFT';
    public const RIGHT = 'RIGHT';
    public const INNER = 'INNER';

    public static function setOperand(&$operand)
    {
        if ($operand == '@OR') {
            return SqlOperator::OR;
        } else if ($operand == '@AND') {
            return SqlOperator::AND;
        } else {
            $ex = new Exception("$operand is not operand. Operand is AND & OR");
            $ds = new dsException($ex, __FILE__);
            $ds->show_exception(true);
            die();
        }
    }
}
