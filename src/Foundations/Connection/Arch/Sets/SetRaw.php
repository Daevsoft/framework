<?php

namespace Ds\Foundations\Connection\Arch\Sets;

class SetRaw extends Set
{
    /**
     * IsRaw
     *
     * @var bool
     */
    public $IsRaw;
    /**
     * __construct
     *
     * @param  string $columnName
     * @param  mixed $value
     * @param  bool $IsRaw
     */
    public function __construct($columnName, $value, $IsRaw = true)
    {
        parent::__construct($columnName, $value, null, null);
        $this->IsRaw = $IsRaw;
    }
}
