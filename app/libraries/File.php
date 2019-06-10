<?php
/**
* File class
* Author by Muhamad Deva Arofi
*/
class File
{
	public $dir;
	public $file;
	function __construct()
	{
		$this->dir = 'assets/files';
	}

	function __destruct()
	{

	}

	public function file($filename,$overwrite = TRUE)
	{
		$newdir = $this->dir.'/'.dirname($filename);
		if (!is_dir($newdir)) mkdir($newdir);
		$this->file = fopen($this->dir.'/'.$filename, ($overwrite ? 'w' : 'a'));
		return $this;
	}
	public function close()
	{
		fclose($this->file);
	}
	public function read($filename)
	{
		return readfile($this->dir.'/'.$filename);
	}
	public function insert($value)
	{
		fwrite($this->file, $value);
		return $this;
	}
	public function form($_urlaction)
	{
		echo "<form action=\"$_urlaction\" method=\"post\" enctype=\"multipart/form-data\">";
	}
	public function upload($_postName)
	{

	}
	public function end_form()
	{
		echo "</form>";
	}
	public function form_submit()
	{
		echo "<input type=\"submit\">";
	}
	public function form_input($key)
	{
		echo "<input type=\"file\" name=\"$key\" id=\"$key\">";
	}
	public function download($filename = '')
	{
		force_download(LINK_FILES.'/'.$filename, basename($filename));
	}
}
