<?php

namespace Ds\Foundations\Commands\Serve;

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Commands\EnvGenerator;
use Ds\Foundations\Commands\Runner;
use Ds\Foundations\Common\Cache;
use Ds\Helper\Str;

class Server extends Runner
{
    private $host;
    private $port;
    private function clearCache()
    {
        $cache = new Cache();
        $cache->clearAllPages();
        $cache->clearReferences();
    }
    private function getAvailablePort($port)
    {
        $this->port = $port;
        $isPortOpened = false;
        do {
            $connection = @fsockopen($this->host, $this->port);
            $isPortOpened = is_resource($connection);
            if ($isPortOpened) {
                $this->port += 1;
                fclose($connection);
            } else {
                return $this->port;
            }
        } while ($isPortOpened);
    }
    public function serve($_host)
    {
        $this->clearCache();

        $envGenerator = new EnvGenerator();
        $envGenerator->run();

        $this->host = $_host == '' ? '127.0.0.1' : $_host;
        // is command is run
        // get a new port for web server
        $this->port = $this->getAvailablePort(8000);

        if (trim($this->host) != STRING_EMPTY) {
            $_serverRun = $this->host . ':' . $this->port;
            Console::writeln("Ds server started on :", Console::LIGHT_BLUE);
            Console::writeln("");
            Console::writeln("\thttp://" . $_serverRun, Console::LIGHT_GREEN);
            Console::writeln("");
            Console::write("Ctrl+C to exit the server\n", Console::DARK_GRAY);
            // Open browser automatically
            $this->launchBrowser($_serverRun);
            $this->startServer($_serverRun);
        } else {
            Console::write('Failed to connect !', Console::RED);
        }
    }
    private function launchBrowser($host)
    {
        $win = Str::contains($_SERVER['OS'], 'windows');
        $mac = Str::contains($_SERVER['OS'], 'mac');
        // For Windows OS
        if ($win) {
            exec("explorer \"http://" . $host . "\"");
        }

        if ($mac) {
            ("open \"http://" . $host . "\"");
        }
    }
    private function startServer($host)
    {
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];

        $process = proc_open('php -S ' . $host . ' -t public', $descriptorspec, $pipes);

        if (is_resource($process)) {
            while ($line = fgets($pipes[2])) {
                if (Str::contains($line, 'Closing') || Str::contains($line, 'Accepted')) {
                    continue;
                }
                $this->printLine($line);
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);
        } else {
            Console::writeln('Failed to start webserver!', Console::LIGHT_RED);
        }
    }
    private function printLine($line)
    {
        $output = preg_replace([
            '/\[.*(' . date('Y') . '\]\s)(.*)/',
        ], [
            '\2',
        ], $line);
        $str = explode(' ', $output);
        if ($str[0] == 'PHP') {
            return;
        }
        $requestCode = $str[1];
        $methodRequest = $str[2];
        Console::write(date('d-m-Y h:i:s') . ' ', Console::DARK_GRAY);
        $output = implode(' ', array_slice($str, 3));
        Console::write($requestCode . ' ');
        $this->printMethodRequest($methodRequest);
        Console::write($output, Console::DARK_GRAY);
    }
    private function printMethodRequest($methodName)
    {
        $color = null;
        switch ($methodName) {
            case 'GET':
                $color = Console::GREEN;
                break;
            case 'POST':
                $color = Console::YELLOW;
                break;
            case 'PUT':
                $color = Console::BLUE;
                break;
            case 'DELETE':
                $color = Console::RED;
                break;

            default:$color = Console::DEFAULT;
                break;
        }
        Console::write($methodName . ' ', $color);
    }
    private function title()
    {
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
