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
            'title'       => 'DS Framework',
            'welcomeText' => 'Let\'s make a better world!',
            'buttonText'  => 'Get Started'
        );
        view("welcome.pie", $data);
    }
}
