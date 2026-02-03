<?php
namespace Ds\Console\Commands;

use Ds\Console\Command;

class FpmServe extends Command
{
    /**
     * Command name and description
     */
    protected string $name = 'serve';
    protected string $description = 'Serve the application using PHP-FPM';

    /**
     * Execute the command
     */
    public function handle(): int
    {
        $host = 'localhost';
        $port = '8000';
        // start PHP-FPM server
        $hostArg = $this->getParameters()[0] ?? '';
        if(strstr($hostArg, '--host=')){
            $host = explode('=', $hostArg)[1];
        }
        $portArg = $this->getParameters()[1] ?? '';
        if(strstr($portArg, '--port=')){
            $port = explode('=', $portArg)[1];
        }
        $command = sprintf('php -S %s:%s -t public', $host, $port);
        $this->info("Starting PHP-FPM server at http://{$host}:{$port}");
        passthru($command);
        return 0;
    }
}