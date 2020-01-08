<?php
class Auth 
{
    function __construct($key_code = '')
    {
        $this->register_id($key_code);
    }

    public function register_id($key_code)
    {
        if(!string_empty($key_code)){
            if(!$this->isRegistered($key_code))
                $_SESSION[$key_code] = md5(time().'ds');
        }
    }

    public function get_auth_id($key_code)
    {
        $auth_id = STRING_EMPTY;
        if (isset($_SESSION[$key_code])) {
            $auth_id = $_SESSION[$key_code];
        }
        return $auth_id;
    }
    public function start($key_code, $req){
        $result = FALSE;
        if($this->isRegistered($key_code)){
            $result = $_SESSION[$key_code] == $req;
        }
        return $result;
    }
    public function isRegistered($key_code)
    {
        return isset($_SESSION[$key_code]);
    }
    public function destroy($key_code)
    {
        if($this->isRegistered($key_code)){
            unset($_SESSION[$key_code]);
        }
    }
}
