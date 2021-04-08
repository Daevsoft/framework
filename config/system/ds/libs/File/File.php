<?php
class FileMode{
    public static $readOnly = 'i'; // Open for reading only
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
    public function clean()
    {
        $this->stream = fopen($this->filename, FileMode::$writeOnly);
    }
    public function rewrite($contents)
    {
        fwrite($this->stream, STRING_EMPTY);
        fclose($this->stream);
    }
    public function create($contents = STRING_EMPTY)
    {
        
    }
    public function delete()
    {
        
    }
    public function getContent()
    {
        
    }
    public function getFilename()
    {
        return $this->filename;
    }
}
