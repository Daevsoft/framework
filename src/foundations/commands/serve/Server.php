<?php

namespace Ds\Foundations\Commands\Serve;

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Commands\EnvGenerator;
use Ds\Foundations\Commands\Runner;
use Ds\Foundations\Common\Cache;
use Ds\Helper\Str;

class Server extends Runner
{
    public function createSocketHost($host, $port, $error)
    {
        error_reporting(1);
        while (fsockopen($host, $port, $error) == TRUE) {
            $port++;
            echo "fail : $port\n";
        }
    }
    private function clearCache(){
        $cache = new Cache();
        $cache->clearAllPages();
        $cache->clearReferences();
    }
    public function serve($_host)
    {
        $this->clearCache();

        $envGenerator = new EnvGenerator();
        $envGenerator->run();
        
        // get directory target, if it's root=public/
        $dir = $this->options[0] ?? STRING_EMPTY;
        $_host = $_host == '' ? 'localhost' : $_host;
        // is command is run
        // get a new port for web server
        // $root = ($dir == STRING_EMPTY || strstr('root', $dir) == STRING_EMPTY) ?
        //     '-t ' : STRING_EMPTY;
        $port = 8000;
        $err = '';
        // check active port
        $this->createSocketHost($_host, $port, $err);

        if (trim($_host) != STRING_EMPTY) {
            $_serverRun = $_host . ':' . $port;
            Console::write('Ds server started on http://' . $_serverRun . "\n", Console::LIGHT_GREEN);
            Console::write("Ctrl+C to exit the server\n", Console::DARK_GRAY);
            // Open browser automatically
            $win = Str::contains($_SERVER['OS'], 'windows');
            $mac = Str::contains($_SERVER['OS'], 'mac');
            // For Windows OS
            if ($win)
                exec("explorer \"http://" . $_serverRun . "\"");
            if ($mac) ("open \"http://" . $_serverRun . "\"");

            // Start web server command
            exec('php -S ' . $_serverRun . ' -t public');
        } else {
            Console::write('Failed to connect !', Console::RED);
        }
    }
    private function title(){
        Console::write("
 ___      __                                   _   
|   \ ___/ _|_ _ __ _ _ __  _____ __ _____ _ _| |__
| |) (_-<  _| '_/ _` | '  \/ -_) V  V / _ \ '_| / /
|___//__/_| |_| \__,_|_|_|_\___|\_/\_/\___/_| |_\_\\\n\n");
    }
    public function run()
    {
        $this->title();
        Console::write("Initializing..\n", Console::LIGHT_GREEN);
        $this->serve($this->options[0] ?? null);
    }
}
