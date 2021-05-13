<?php
function auth(){
    Auth::validation();
}
class Auth 
{
    public static function init($user, $fail_target = 'login')
    {
        $user = (object)$user;
        session('user-auth', serialize($user));
        session('auth-fail-redirect', $fail_target);
    }
    public static function validation()
    {
        if(session('user-auth') == null){
            Auth::redirect();
        }
    }
    public static function is_logged()
    {
        if(session('user-auth') != null){
            redirect();
        }
    }
    public static function user()
    {
        return unserialize(session('user-auth'));
    }
    public static function logout()
    {
        unsession('user-auth');
        Auth::redirect();
    }
    public static function redirect()
    {
        redirect(session('auth-fail-redirect') ?? 'login');
    }
}
