<?php

namespace Ds\Foundations\Commands;

use Ds\Dir;
use Ds\Foundations\Commands\Generator\AddFile;
use Ds\Foundations\Commands\Serve\Server;
use Ds\Helper\Str;

class Terminal
{
    private $args;
    private $commandList = [
        'serve' => Server::class,
        'add:*' => AddFile::class,
        'config' => EnvGenerator::class
    ];

    public function __construct($argv)
    {
        Dir::init();
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
        if(Str::contains($command, ':')){
            $otherArg = substr($command, strpos($command, ':') + 1);
            $command = substr($command, 0, strpos($command, ':')+1).'*';
        }
        if (isset($this->commandList[$command])) {
            $options = count($this->args) > 1 ? array_slice($this->args, 1) : [];
            if(!Str::empty($otherArg)){
                $options = [$otherArg, ...$options];
            }
            $runner = new $this->commandList[$command]($options);
            $runner->run();
        } else {
            Console::write('Command [' . $this->args[0] . '] not found!');
            die();
        }
    }
}
