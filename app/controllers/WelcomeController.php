<?php
/**
 * WelcomeController
 */
class WelcomeController extends dsController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data = array(
            'welcomeText' => 'Easy to Develop, Easy to Do, and Smooth.'
        );
        view("welcome", $data);
    }
}
