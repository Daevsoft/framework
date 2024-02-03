<?php

namespace Ds\Foundations\Connection\Arch\Sets;

class Set
{
    /**
     * IsFromChild
     *
     * @var bool
     */
    public $IsFromChild;
    /**
     * Column
     *
     * @var string
     */
    public $Column;
    /**
     * BindName
     *
     * @var string
     */
    public $BindName;
    /**
     * VType parameter type for binding
     *
     * @var int
     */
    public $VType;
    /**
     * Value value of binding parameter
     *
     * @var object
     */
    public $Value;
    /**
     * CustomBind custom binding value with raw
     *
     * @var \Closure
     */
    public $CustomBind;

    public function __construct($columnName, $value, $dbType, $bindName, $customBind = null)
    {
        $this->Column = $columnName;
        $this->BindName = $bindName;
        $this->Value = $value;
        $this->VType = $dbType;
        $this->CustomBind = $customBind;
    }
}
