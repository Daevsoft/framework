<?php

use Ds\AppIndex;
use Ds\Core\Ds;
use Ds\Foundations\Config\Env;
use Ds\Foundations\Validator\Validator;
use Ds\Foundations\View\PageProvider;
use Ds\Foundations\View\View;

if (! function_exists('asset')) {
    function asset($_fileName)
    {
        return AppIndex::$BASE_ASSETS . $_fileName;
    }
}

function view($viewname = 'index', $data = [], $slots = null)
{
    $viewname = View::filename($viewname);
    ob_start();
    if ($viewname != null) {
        $page = PageProvider::init();
        $page->__page($viewname, $data, $slots);
    }
    $contents = ob_get_contents();
    ob_end_clean();
    if (Ds::$appEngine == 'swoole') {
        // Swoole handle exception
        return $contents;
    }

    if (Ds::$appEngine != 'swoole') {
        echo $contents;
        return;
    }
    return $contents;
}
function session($key, $default = null)
{
    $sm = \Ds\Foundations\Session\SessionManager::init();
    if (is_string($key)) {
        return $sm->get($key, $default);
    } else if (is_array($key)) {
        foreach ($key as $k => $v) {
            $sm->put($k, $v);
        }
    }
}
function unsession($key)
{
    \Ds\Foundations\Session\SessionManager::init()->delete($key);
}
function flash($key, $defaultValue = null)
{
    $key   = 'flash__' . $key;
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
function old($key, $defaultValue = null)
{
    return Validator::oldValue($key, $defaultValue);
}
function js_source($src)
{
    if (Env::get('STATUS') == 'production') {
        echo '<script src="/public/assets/js/' . $src . '.js"></script>';
    } else {
        echo '<script src="/assets/js/' . $src . '.js"></script>';
    }
}
function app_url()
{
    return sprintf(
        "%s://%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME']
    );
}
