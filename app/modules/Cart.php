<?php 

class Cart
{
    protected $count;
    public function __construct()
    {
        global $count;
        if (isset($_SESSION['_cart_temp'])) {
            $count = count($_SESSION['_cart_temp']);
        }
    }

    public function __get($val)
    {
        return $this->$val;
    }

    public function insert($value = array())
    {
    	$len = count($_SESSION['_cart_temp']);
        $_SESSION['_cart_temp'][$len] = $value;
    }

    public function count()
    {
        global $count;
        return $count;
    }

    public function get_contents()
    {
        return $_SESSION['_cart_temp'];
    }

    public function destroy()
    {
        $_SESSION['_cart_temp'] = NULL;
    }

}