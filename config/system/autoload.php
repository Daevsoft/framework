<?php
class _autoload2f40af1f10ad60c89a4b333ee7943d49
{
	public static function getLoader()
	{
		$source_files = require_once Indexes::$DIR_SYSTEM.'autoload_files.php';

		foreach ($source_files as $file) {
			$file_dir = Indexes::$DIR_SYSTEM. $file;
			if (file_exists($file_dir)) {
				require_once $file_dir;
			}else{
				die("Error : Some files not found.");
			}
		}
	}
}
