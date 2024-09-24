<?php

use Ds\AppIndex;
use Ds\Foundations\Config\Env;
use Ds\Foundations\View\PageProvider;
use Ds\Foundations\View\View;
use Ds\Foundation\View\Slot;

if (!function_exists('asset')) {
    function asset($_fileName)
    {
        return AppIndex::$BASE_ASSETS . $_fileName;
    }
}
if (!function_exists('get_slot')) {
    function get_slot($_fileName)
    {
        echo Slot::getSlot($_fileName);
    }
}

function view($viewname = 'index', $data = [])
{
    $viewname = View::filename($viewname);
    if ($viewname != null) {
        $page = PageProvider::init();
        $page->__page($viewname, $data);
    }
}
function session($key, $default = null)
{
    if (is_string($key)) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    } else if (is_array($key)) {
        $_SESSION = [ ...$_SESSION, ...$key];
    }
}
function unsession($key)
{
    unset($_SESSION[$key]);
}
function flash($key, $defaultValue = null)
{
    $key = 'flash__' . $key;
    $flash = session($key) ?? $defaultValue;
    unsession($key);
    return $flash;
}
function is_flash($key)
{
    $key = 'flash__' . $key;
    return isset($_SESSION[$key]);
}
function set_flash($key, $content)
{
    $key = 'flash__' . $key;
    session([$key => $content]);
}
function js_source($src)
{
    if (Env::get('STATUS') == 'production') {
        echo '<script src="/public/assets/js/' . $src . '.js"></script>';
    } else {
        echo '<script src="/assets/js/' . $src . '.js"></script>';
    }
}
