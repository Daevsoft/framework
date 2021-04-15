<?php
require_once Indexes::$DIR_EVENTS.'EventProvider.php';

class Event
{
    public static $registered_event = [];

    private $defined_events = [];
    public static $me = NULL;

    public function do(){}
    public static function register($event_list)
    {
        self::$registered_event = $event_list;

        foreach ($event_list as $name => $class_name){
            require_once Indexes::$DIR_EVENTS.$class_name.Key::EXT_PHP;
        }
        self::$me = new Event();
    }
    public static function call($event_name){
        if(!isset(Event::$me->defined_events[$event_name])){
            Event::$me->defined_events[$event_name] = new self::$registered_event[$event_name]();
        }
        Event::$me->defined_events[$event_name]->do();
    }
}
if (! function_exists('event')) {
	function event($event)
	{
		if(is_string($event)){
            Event::call($event);
        }
	}
}