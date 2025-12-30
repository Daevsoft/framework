<?php
namespace Ds\Foundations\Controller;

use Ds\Foundations\Provider;

class Controller implements Provider
{
    public function install()
    {
        //Func::check('Controller installed !');
    }
    public function run($param = null)
    {
        //Func::check('Controller running..');
    }
    public function redirect($route): void
    {
        header('Location: ' . $route);
    }
}
