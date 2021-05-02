<?php
require_once Indexes::$DIR_EVENTS.'EventProvider.php';

class Event
{
    public static $registered_event = [];

    private $defined_events = [];
    protected $data = NULL;
    public static $me = NULL;

    public function do(){}
    public function data($data)
    {
        $this->data = $data;
    }
    public static function register($event_list)
    {
        self::$registered_event = $event_list;

        foreach ($event_list as $name => $class_name){
            require_once Indexes::$DIR_EVENTS.$class_name.Key::EXT_PHP;
        }
        self::$me = new Event();
    }
    public static function call($event_name, $data){
        if(!isset(Event::$me->defined_events[$event_name])){
            Event::$me->defined_events[$event_name] = new self::$registered_event[$event_name]();

            if($data != NULL){
                Event::$me->defined_events[$event_name]->data($data);
            }
        }
        Event::$me->defined_events[$event_name]->do();
    }
}
if (! function_exists('event')) {
	function event($event, $data = NULL)
	{
		if(is_string($event)){
            Event::call($event, $data);
        }elseif ($event instanceof Event) {
            $event->do();
        }
	}
}