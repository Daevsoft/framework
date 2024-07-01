<?php
namespace Ds\Foundations\Common;


class File
{
    public const READ_ONLY = 'r'; // Read only. Starts at the beginning of the file
    public const READ_PLUS = 'r+';// Read/Write. Starts at the beginning of the file
    public const WRITE_ONLY = 'w'; // Write only. Opens and truncates the file; or creates a new file if it doesn't exist.
    public const WRITE_PLUS = 'w+';// Read/Write. Opens and truncates the file; or creates a new file if it doesn't exist.
    public const READ_WRITE = 'a+';// Read/Write. Preserves file content by writing to the end of the file
    public const CREATE_WRITE = 'x'; // Write only. Creates a new file. Returns FALSE and an error if file already exists
    public const CREATE_READ_WRITE = 'x+'; // Read/Write. Creates a new file. Returns FALSE and an error if file already exists

    private $path;
    private $filename;
    private $stream;
    private $mode;
    
    public function __construct($path, $mode = self::READ_WRITE) {
        $this->path = $path;
        $this->filename = basename($path);
        $this->mode = $mode;
    }
    private function checkDir($dir){
        if(!is_dir($dir)){
            mkdir($dir);
        }
    }
    public function close()
    {
        fclose($this->stream);
    }
    public function rewrite($contents)
    {
        $this->stream = fopen($this->path, self::WRITE_PLUS);
        fwrite($this->stream, $contents);
        return $this;
    }
    public function append($contents){
        $this->stream = fopen($this->path, self::READ_WRITE);
        fwrite($this->stream, $contents);
        return $this;
    }
    public function create($contents = STRING_EMPTY)
    {
        $this->checkDir(dirname($this->path));
        $this->stream = fopen($this->path, self::READ_WRITE);
        fwrite($this->stream, $contents);
        return $this;
    }
    public function delete()
    {
        
    }
    public function getContent()
    {
        $this->stream = fopen($this->path, self::READ_ONLY);
        $string = fread($this->stream,filesize($this->path));
        $this->close();
        return $string;
    }
    public function getFilename()
    {
        return $this->filename;
    }
}
