<?php
namespace Ds\Helper;

class FileMode{
    public static $readOnly = 'r'; // Open for reading only
    public static $readPlus = 'r+';// Open for reading and writing
    public static $writeOnly = 'w'; // Open for writing only
    public static $writePlus = 'w+';// Open for writing only
    public static $readWrite = 'a+';// Open for reading and writing
    public static $createWrite = 'x'; // Create and open for writing only
    public static $createReadWrite = 'x+'; // Create and open for reading and writing
}
class File
{
    private $path;
    private $filename;
    private $basename;
    private $stream;
    public function __construct($path) {
        $this->path = $path;
        $this->filename = basename($path);
    }
    public function close()
    {
        fclose($this->stream);
    }
    public function rewrite($contents)
    {
        $this->stream = fopen($this->path, FileMode::$writePlus);
        fwrite($this->stream, $contents);
        return $this;
    }
    public function create($contents = STRING_EMPTY)
    {
        $this->stream = fopen($this->path, FileMode::$createWrite);
        fwrite($this->stream, $contents);
        return $this;
    }
    public function delete()
    {
        
    }
    public function getContent()
    {
        $this->stream = fopen($this->path, FileMode::$readOnly);
        $string = fread($this->stream,filesize($this->path));
        $this->close();
        return $string;
    }
    public function getFilename()
    {
        return $this->filename;
    }
}
