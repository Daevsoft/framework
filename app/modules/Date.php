<?php
class Date
{
    private $timeValue;
    public $dateValue;
    function __construct($value = '')
    {
        $this->timeValue = strtotime($value);
        if(!string_empty($value))
            $this->dateValue = date('d-m-Y', $this->timeValue);
    }
    public function format($format = 'd-m-Y')
    {
        if(string_empty_or_null($this->timeValue))
            return STRING_EMPTY;
        return date($format, $this->timeValue);
    }
}
