<?php
class Message extends Broadcast
{
    public function __construct($channel) {
        parent::__construct($channel);
    }
    public function message($message)
    {
        $this->message = $message;
    }
}
