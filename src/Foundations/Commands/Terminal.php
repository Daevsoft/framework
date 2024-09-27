<?php

namespace Ds\Foundations\Commands;

use Ds\Dir;
use Ds\Foundations\Commands\CronJob\Worker;
use Ds\Foundations\Commands\Generator\AddFile;
use Ds\Foundations\Commands\Serve\Server;
use Ds\Foundations\Commands\Tester\Tester;
use Ds\Foundations\Common\Cache;
use Ds\Foundations\Common\File;
use Ds\Foundations\Migrate\Migration;
use Ds\Helper\Str;

class Terminal
{
    private $args;
    private $commandList = [
        'serve' => Server::class,
        'add:*' => AddFile::class,
        'config' => EnvGenerator::class,
        'test' => Tester::class,
        'cronjob' => Worker::class,
        'migrate' => Migration::class,
        'cache' => Cache::class,
    ];
    private $autoload = [
        'storage' . SLASH . 'cache' . SLASH . 'config.temp.php',
        'app' . SLASH . 'functions' . SLASH . 'fun.php',
        'vendor' . SLASH . 'daevsoft' . SLASH . 'framework' . SLASH . 'src' . SLASH . 'Foundations' . SLASH . 'Commands' . SLASH . 'Tester' . SLASH . 'TesterFunc.php',
        'vendor' . SLASH . 'phpunit' . SLASH . 'phpunit' . SLASH . 'src' . SLASH . 'Framework' . SLASH . 'Assert' . SLASH . 'Functions.php',
    ];

    private function setupTerminal()
    {
        Dir::init();
        foreach ($this->autoload as $filename) {
            $autoloadFile = Dir::$MAIN . $filename;
            if (file_exists($autoloadFile)) {
                require_once $autoloadFile;
            }
        }
        $this->initRoute();
    }

    private function initRoute()
    {
        $fileRoutes = Dir::$ROUTE . 'web.php';
        if (!file_exists($fileRoutes)) {
            if (!is_dir(Dir::$ROUTE)) {
                mkdir(Dir::$ROUTE, 7777, true);
            }
            $routeContent = "
            <?php\n
            use App\Controllers\IndexController;\n
            use Ds\Foundations\Routing\Route;\n
            \n
            Route::get('/', [IndexController::class, 'index']);\n";
            (new File($fileRoutes))->create($routeContent)->close();
        }
    }

    public function __construct($argv)
    {
        $this->setupTerminal();
        $this->args = array_slice($argv, 1);
        $this->validate();
    }
    private function validate()
    {
        if (count($this->args) == 0) {
            Console::write('Command can\'t be empty!');
            die();
        }
    }

    public function start()
    {
        $command = $this->args[0];
        $otherArg = '';
        if (Str::contains($command, ':')) {
            $otherArg = substr($command, strpos($command, ':') + 1);
            $command = substr($command, 0, strpos($command, ':') + 1) . '*';
        }
        if (isset($this->commandList[$command])) {
            $options = count($this->args) > 1 ? array_slice($this->args, 1) : [];
            if (!Str::empty($otherArg)) {
                $options = [$otherArg, ...$options];
            }
            $runner = $this->createInstanceRunner($command, $options);
            $runner->run();
        } else {
            Console::write('Command [' . $this->args[0] . '] not found!');
            die();
        }
    }
    private function createInstanceRunner($name, $options): mixed
    {
        return new $this->commandList[$name]($options);
    }
}
