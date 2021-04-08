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
            'welcomeText' => 'Let\'s build an awesome app!',
            'buttonText'  => 'Get Started'
        );
        view("welcome.pie", $data);
    }
}
