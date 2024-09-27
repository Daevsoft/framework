<?php

namespace Ds\Foundations\View;

use Ds\Dir;
use Ds\Foundations\Exceptions\dsException;

// enc: SHA1
class CacheView
{
    private static $arr_times = null;
    public $encryptedFile;
    private $modified_time;
    private $result_last_time;

    public function checkRootUpdated($filename)
    {
        $this->modified_time = filemtime($filename);

        $source = file_get_contents($filename);
        $matches = [];
        preg_match_all('/\@use\(\'(.*)\'\)/i', $source, $matches);
        $rootUpdated = false;
        $roots = $matches[1];
        if (count($roots) > 0) {
            foreach ($roots as $rootfilename) {
                $pie = Dir::$VIEWS . $rootfilename . '.pie';
                $piePhp = $pie . '.php';
                if (file_exists($piePhp)) {
                    $cacheView = new CacheView($piePhp);
                    $rootUpdated = $cacheView->is_modified();
                    if ($rootUpdated) {
                        $cacheView->recordViewTime();
                    }
                } else {
                    $ex = new dsException('File ' . $rootfilename . '.php does not exist!', $filename, -1);
                    $ex->show_exception(true);
                    die();
                }
            }
        }
        return $rootUpdated;
    }
    public function getEncryptedFileName($filename)
    {
        if (isset(self::$arr_times[$filename])) {
            return self::$arr_times[$filename];
        } else {
            $encrypted = sha1($filename) . '.php';
            $this->recordToFile($filename, $encrypted);
            return self::$arr_times[$filename];
        }
    }
    public function recordViewTime()
    {
        $this->recordToFile($this->encryptedFile, $this->modified_time);
    }
    public function __construct($real_filename)
    {
        if (self::$arr_times == null) {
            self::$arr_times = require_once Dir::$CACHE_TIME;
        }
        $isRootUpdated = $this->checkRootUpdated(filename: $real_filename);
        if ($isRootUpdated) {
            $this->modified_time = time();
        }
        $this->encryptedFile = $this->getEncryptedFileName($real_filename);
        $this->result_last_time = $this->cache_record($this->encryptedFile);
    }
    private function cache_record($filename)
    {
        $cache_pathfile = Dir::$CACHE_VIEW . $filename;
        if (!isset(self::$arr_times[$filename]) && file_exists($cache_pathfile)) {
            $this->recordToFile($filename, time());
        }
        return self::$arr_times[$filename] ?? 0;
    }
    public function is_modified()
    {
        return $this->result_last_time != $this->modified_time;
    }
    public function exists()
    {
        return file_exists(Dir::$CACHE_VIEW . $this->encryptedFile);
    }
    public function recordToFile($key, $value)
    {
        self::$arr_times = require Dir::$CACHE_TIME;
        if (self::$arr_times == '') {
            self::$arr_times = [];
        }
        // $temp_list_item = "\t'" . $this->encryptedFile . '\'=>\'' . $this->modified_time . "',";
        // $temp_list_item_last = "\t'" . $this->encryptedFile . '\'=>\'' . $this->result_last_time . "',";
        self::$arr_times[$key] = $value;
        // Set new status cache
        $temp_content = $this->tempToPhp();

        // if (empty($this->result_last_time)) {
        //     $temp_content = str_replace('];', $temp_list_item . "\n];", $temp_content);
        // } else {
        //     $temp_content = str_replace($temp_list_item_last, $temp_list_item, $temp_content);
        // }
        file_put_contents(Dir::$CACHE_TIME, $temp_content);
    }
    private function tempToPhp()
    {
        $temp = '<?php return [';
        foreach (self::$arr_times as $key => $value) {
            $temp_list_item = "\t'" . $key . '\'=>\'' . $value . "',";
            $temp .= $temp_list_item;
        }
        $temp .= '];';
        return $temp;
    }
}
