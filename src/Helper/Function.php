<?php

use Ds\AppIndex;
use Ds\Foundations\View\PageProvider;
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
    if ($viewname != null) {
        $page = new PageProvider();
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
function session_end()
{
    session_destroy();
}
function flash($key, $defaultValue = null)
{
    $key = 'flash__' . $key;
    $flash = session($key) ?? $defaultValue;
    unsession($key);
    return $flash;
}
function set_flash($key, $content)
{
    session('flash__' . $key, $content);
}
