<?php 
// Get pattern IP Address
$array_ip = explode('.', $ipAddress['ip_pattern']); $ip_request = explode('.', $_SERVER['REMOTE_ADDR']);
//Validate ip address in pattern
if (!(($array_ip[0] == "*" || $array_ip[0] == $ip_request[0]) && 
	  ($array_ip[1] == "*" || $array_ip[1] == $ip_request[1]) && 
	  ($array_ip[2] == "*" || $array_ip[2] == $ip_request[2]) && 
	  ($array_ip[3] == "*" || $array_ip[3] == $ip_request[3]))) {
		die("<h3>Access denied!</h3>");
}
// Validate ip address in ip_list
if ($ipAddress['ip_list'] != 'any') {
	if (!string_contains($_SERVER['REMOTE_ADDR'], $ipAddress['ip_list'])) {
		die("Cannot access this website because permission is not allowed!");
	}
}