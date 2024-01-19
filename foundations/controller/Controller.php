<?php

namespace Ds\Foundations\Controller;

use Ds\Foundations\Common\Func;
use Ds\Foundations\Provider;

class Controller implements Provider
{
    function install()
    {
        Func::check('Controller installed !');
    }
    function run()
    {
        Func::check('Controller running..');
    }
}
