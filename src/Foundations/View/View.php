<?php
namespace Ds\Foundations\View;

use Ds\Helper\Str;

class View
{
    public static function contents($viewName, $data = null)
    {
        $contents = view($viewName, $data);
        return $contents;
    }
    public static function filename($viewName)
    {
        return Str::replace($viewName, '.', SLASH);
    }
}
