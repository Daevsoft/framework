<?php

namespace Ds\Foundations\Session;

use Ds\Foundations\Provider;

class SessionProvider implements Provider
{
    public function run() {}
    public function install()
    {
        session_start();
    }
}
