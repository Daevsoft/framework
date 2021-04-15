<?php 
class dsSystem
{
	public static function Message($msg)
	{
		$status = config('status');
		if ($status != Key::PUBLISHED && $status != Key::DEVELOPMENT) {
			echo '<br><div style="background-color:darkorange;color:white"><b>Message</b> : </div>';
		}
		if ($status == 'debugging') {
			echo $msg.'</div>';
		}
		if ($status == Key::DEVELOPMENT) {
			
		}
	}
	public static function MessageError()
	{
		$status = config('status');
		
		$ArgLen = func_num_args();
		$msg = $ArgLen == 2 ? func_get_arg(1) : func_get_arg(0);

		if (uri(0) !== 'api') {
			$_file_name = $ArgLen  == 2 ? '<br>(file:'.func_get_arg(0).')' : STRING_EMPTY;
			echo $_file_name.'<br>(#'.$status.') : ';
			if ($status == 'debugging') {
				echo $msg;
			}else if ($status == Key::DEVELOPMENT) {
				echo($msg);
			}else{
				die('Sorry Nothing to do Here');
			}
		}else{
			header('Content-Type: application/json');
			echo json_encode(['error' => $msg]);
			die();
		}
	}
	// Filtering text input form
	public static function fill_text(&$__tx)
	{
		return filter_var(
			stripslashes(
				strip_tags(
					htmlspecialchars(
						trim($__tx))))
			, FILTER_SANITIZE_STRING);
	}
	// Check token in session has Valid
	public static function secure()
	{
		global $__tfile;
		$isset_token = (isset($_SESSION['session_token_']) && isset($_SESSION['session_token_valid']));
		if (!$isset_token) {
			if (config('status') == 'published') { // if token not valid and status is published
				die('<h2>Token failure !</h2>');
			}
		}else{
			$token = $_SESSION['session_token_'];
			$token_len = strlen($token);
			$compare_valid_token = ($_SESSION['session_token_valid'] == (substr($token,$token_len / 2)));
			if ($compare_valid_token) {
				if ($__tfile != $token) { //  Compare token in token file and token session
					die('<h2>Token failure !</h2>');
				}
			}
		}
	}
}
