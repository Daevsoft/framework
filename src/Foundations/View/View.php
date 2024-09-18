<?php
namespace Ds\Foundations\View;

class View
{
    public static function contents($viewName, $data = null)
    {
        ob_start();
        view($viewName, $data);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}
