<?php
function auth(){
    Auth::validation();
}
class Auth 
{
    public static function init($user, $fail_target = 'login')
    {
        session('user-auth', json_encode($user));
        session('auth-fail-redirect', $fail_target);
    }
    public static function validation()
    {
        if(session('user-auth') == null){
            Auth::fail();
        }
    }
    public static function is_logged()
    {
        if(session('user-auth') != null){
            redirect();
        }
    }
    public function user()
    {
        return session('user-auth');
    }
    public static function logout()
    {
        session_remove('user-auth');
        // test(session('auth-fail-redirect'));
    }
    public static function fail()
    {
        test(session('auth-fail-redirect'));
        redirect(session('auth-fail-redirect'));
    }
}
