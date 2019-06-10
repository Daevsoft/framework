<?php 
/*
	Write global functions here...
*/
// Example :
function page($_page, $data = [])
{
    FrontEnd::page($_page, $data);
}
// Get page as string
function get_page($_page, $data = [])
{
	return get_view($_page, $data);
}
function app_name()
{
	return config('app_name');
}
