<?php

namespace Ds\Foundations\Connection\Arch\Sets;

class SetWhere extends Set
{
    public $ValueOperator = "=";
    public $Operator = "AND"; // AND OR

    /**
     * __construct
     *
     * @param string $columnName
     * @param object $value
     * @param string $oOperator
     * @param int $dbType
     * @param \Closure $customBind
     * @param string $operator
     * @return void
     */
    public function __construct($columnName, $value, $oOperator, $dbType, $customBind = null, $operator = 'AND')
    {
        parent::__construct($columnName, $value, $dbType, $columnName, $customBind);
        $this->ValueOperator = $oOperator;
        $this->Operator = $operator;
    }
}
