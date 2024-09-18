<?php

namespace Ds\Core;

use Ds\AppIndex;
use Ds\Dir;
use Ds\Foundations\Config\Env;
use Ds\Foundations\Connection\DatabaseProvider;
use Ds\Foundations\Controller\Controller;
use Ds\Foundations\Debugger\Debug;
use Ds\Foundations\Exceptions\dsException;
use Ds\Foundations\Routing\RouteProvider;
use Ds\Foundations\Session\SessionProvider;
use Ds\Foundations\Validator\ValidationProvider;
use Ds\Foundations\View\PageProvider;

class Ds
{
    private array $providers;

    private $debugbarRenderer;
    private $isDebug = null;

    public function __construct()
    {
        Dir::init();
        include_once Dir::$CONFIG_TEMP;
        dsException::init();
        $this->initDebugger();

        AppIndex::init();
        $this->providers = [
            new SessionProvider(),
            new DatabaseProvider(),
            new PageProvider(),
            new RouteProvider(),
            new Controller(),
            new ValidationProvider(),
        ];
        $this->loadProviders();
    }
    private function initDebugger()
    {
        $this->isDebug = Env::get('DEBUG_BAR') == 'true';
        if ($this->isDebug) {
            Debug::init();
        }
    }
    private function loadProviders()
    {
        foreach ($this->providers as $provider) {
            $provider->install();
        }
    }
    public function connect()
    {
        foreach ($this->providers as $provider) {
            $provider->run();
        }
        Debug::writeLog();
    }
}
