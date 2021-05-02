<?php
use Pusher\Pusher;

class Broadcast
{
    private $config;
    private $pusher;
    protected $channel;
    // public function message($message);
    public function send($event, $message)
    {
        $data['data'] = $message;
        if($this->channel != null)
            return $this->pusher->trigger($this->channel, $event, $data);
    }
    public function __construct($channel)
    {
        $this->channel = $channel;

        $this->config = config('broadcast')['connections'];
        $connection = $this->config['main']; // TODO !! optional with alternative

        $options = $connection['options'];
        $this->pusher = new Pusher(
            $connection['auth_key'],
            $connection['secret'],
            $connection['app_id'],
            $options
        );
    
    }
}
