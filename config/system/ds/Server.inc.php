<?php
require_once __DIR__.'/libs/File/File.php';
require_once __DIR__.'/utils/Session.inc.php';

class Server extends Ds
{
    public function __construct() {
    }
    public static function createSocketHost($host, $port, $error)
    {
        error_reporting(1);
        while(fsockopen($host, $port, $error) == TRUE){
            $port++;
            echo "fail : $port\n";
        }
    }
    public static function Run($_host, $_args)
    {
        // clear cache before running
        $session = new Session();
        $cache = new Cache();

        $session->clear();
        $cache->clearAllPages();
        $cache->clearReferences();

        // get directory target, if it's root=public/
        $dir = $_args[SECOND_ARG] ?? STRING_EMPTY;
        $_host = $_host == '' ? 'localhost' : $_host ;
        // is command is run
        // get a new port for web server
        $root = ($dir == STRING_EMPTY || strstr('root', $dir) == STRING_EMPTY) ?
                '-t public/' : STRING_EMPTY; 
        $port = 8000;
        $err = '';
        // check active port
        self::createSocketHost($_host, $port, $err);

        if (trim($_host) != STRING_EMPTY) {
            $_serverRun = $_host.':'.$port;
            msg('Ds server started on http://'. $_serverRun.
            "\nCtrl+C to exit the server");
            // Open browser automatically
            $win = parent::string_contains($_SERVER['OS'], 'windows');
            $mac = parent::string_contains($_SERVER['OS'], 'mac');
            // For Windows OS
            if($win)
                parent::force_cmd("explorer \"http://".$_serverRun."\"");
            if($mac)
                parent::force_cmd("open \"http://".$_serverRun."\"");
            
            // Start web server command
            exec('php -S '.$_serverRun.' '.$root);
        }else{
            msg('Failed to connect !');
        }
    }
}