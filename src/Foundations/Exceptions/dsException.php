<?php

namespace Ds\Foundations\Exceptions;

use Ds\Dir;
use Ds\Foundations\Commands\Console;
use Ds\Foundations\Common\File;
use Ds\Foundations\Common\Func;
use Ds\Foundations\Config\Env;
use Ds\Foundations\Debugger\Debug;
use Exception;
use Throwable;

class dsException extends Exception
{
    private Exception|Throwable $exception;
    private $filename;
    private $filename_real;
    private $additionalMessage = '';
    public static function init()
    {
        set_error_handler(function ($code, $msg, $filename, $line) {
            
            if(Env::get('STATUS') == 'development'){
                $dsE = new dsException($msg, $filename, $line);
                $dsE->show_exception(true);
                Debug::error($dsE);
            }else{
                echo file_get_contents(Dir::$VIEWS.(Env::get('404_PAGE', 'page-404.html')));
            }
            Debug::writeLog();
            die();
        });
        set_exception_handler(function ($ex) {
            if(Env::get('STATUS') == 'development'){
                $dsE = new dsException($ex, $ex->getFile(), $ex->getLine(), $ex->getMessage());
                $dsE->show_exception(true);
                Debug::error($dsE);
            }else{
                echo file_get_contents(Dir::$VIEWS.(Env::get('404_PAGE', 'page-404.html')));
            }
            Debug::writeLog();
            die();
        });
    }
    public function addMessage($message)
    {
        $this->additionalMessage .= $message;
    }
    public function __construct($_exception, $filename = STRING_EMPTY, $line = -1, $msg = null)
    {
        parent::__construct();
        if (!empty($filename)) {
            $this->filename = $filename;
        }
        if (is_string($_exception)) {
            $this->message = $_exception;
            $this->exception = $this;
            $this->file = $filename;
        } else {
            $this->exception = $_exception;
            if (!empty($filename)) {
                $this->filename = $filename;
            }
        }
        if ($line != -1) {
            $this->line = $line;
        }
        if ($msg != null) {
            $this->message = $msg;
        }
        if ($filename == STRING_EMPTY) {
            // if(isset($GLOBALS['FILENAMES'])){
            //     $this->filename = $GLOBALS['FILENAMES'];
            // }else{
            //     $this->filename = $this->exception->getFile();
            // }
            // if(isset($GLOBALS['FILENAMES_REAL'])){
            //     $this->filename_real = $GLOBALS['FILENAMES_REAL'];
            // }
        } else {
            $this->filename_real = $this->filename = $filename;
        }
        // $this->show_exception($show_line);
    }
    public function show_exception(bool $_show_line)
    {
        // Get All Trace
        if ($this->exception instanceof Exception || is_object($this->exception)) {
            // header_remove('Content-Type');
            // header('Content-Type:text/html');
            $arrTrace = $this->exception->getTrace();
            $filename = $this->filename;
            $additionalMessage = $this->additionalMessage ?? '';

            if (isset($_SERVER['SERVER_PROTOCOL'])) {
                include(__DIR__ . SLASH . 'view' . SLASH . 'exception.php');
            } else {
                // save log
                ob_start();
                include(__DIR__ . SLASH . 'view' . SLASH . 'exception.php');
                $content = ob_get_contents();
                ob_clean();
                ob_flush();

                $logFile = new File(Dir::$CACHE . 'log' . SLASH . 'error_' . date('Ymd') . '.html');
                $logFile->create($content)->close();
                
                $errorMsg = '  ERROR : ' . $this->simplePath($this->exception->getFile()) . ' ('.$this->exception->getLine().")\n";
                $errorMsg .= '  Message : ' .$this->exception->getMessage();
                echo "\e[0;41;31m".$errorMsg."\e[0m\n";
                $this->show_cli_trace($arrTrace);
            }
        }
    }
    private function simplePath($path){
        return str_replace(ROOT, '.'.SLASH, $path);
    }
    private function show_cli_trace($arrTrace){
        $len = count($arrTrace);
        for ($i = $len - 1; $i > 0; $i--) {
            $trace = $arrTrace[$i];
            Console::writeln('+'.str_repeat('-', 30), Console::LIGHT_RED);
            Console::writeln('| Trace File: '.$this->simplePath($trace['file']). ' ('.$trace['line'].')', Console::LIGHT_RED);
            Console::writeln('|      Function : '.$trace['function'], Console::LIGHT_RED);
        }
        Console::writeln('+'.str_repeat('-', 30), Console::LIGHT_RED);
    }
    public function display_line_error($_arrFile, $_line)
    {
        $start_line = $_line - 1;
        $end_line = $_line;
        if ($start_line > 10) $start_line -= 10;
        else $start_line = 0;

        if (count($_arrFile) > $end_line + 9) $end_line += 10;
        else $end_line = count($_arrFile);

        $lines = '';
        $codes = '';
        for ($i = $start_line; $i < $end_line; $i++) {
            $line = $i + 1;
            $code = $_arrFile[$i];

            if ($line == $_line) {
                $line = '<span class="error_line"><span class="ds_line_break_error"></span><span>' . $line . '</span></span>';
            }

            $lines .= $line . '<br>';
            $codes .= $code;
        }
        $result2 = '<div style="position: relative; overflow: hidden; ">';
        $result2 .= '<div class="ds_line_break_no">' . $lines . '</div><pre style="background: transparent !important;"><code class="language-php">' . htmlspecialchars($codes) . '</code></pre>';
        $result2 .= '</div>';
        return $result2;
    }
}
