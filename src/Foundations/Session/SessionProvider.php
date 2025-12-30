<?php
namespace Ds\Foundations\Session;

use Ds\Foundations\Provider;

class SessionProvider implements Provider
{
    public function run($param = null)
    {}
    public function install()
    {
        SessionManager::init();
    }
}
