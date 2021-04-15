<?php
class _autoload2f40af1f10ad60c89a4b333ee7943d49
{
	public static function getLoader()
	{
		$source_files = require Indexes::$DIR_SYSTEM.'autoload_files.php';
		$i = 0;
		while (isset($source_files[$i])) {
			$file_dir = Indexes::$DIR_SYSTEM.$source_files[$i];
			if (file_exists($file_dir)) {
				require $file_dir;
			}else{
				echo 'Expected : '.$file_dir.'<br />';
				die("Error : Some files not found.");
			}
			$i++;
		}
	}
}
