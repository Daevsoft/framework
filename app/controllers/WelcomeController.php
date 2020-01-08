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

    public function sample($tes = '',$keep = '')
    {
        echo 'hello '. $tes. ' ' . $keep;
    }

    public function index()
    {
        $data = array(
            'title'       => 'DS Framework',
            'welcomeText' => 'Let\'s make a better world!',
            'buttonText'  => 'Get Started'
        );
        view("welcome.slice", $data);
    }
    public function other($nama = ''){
        $data['nama'] = $nama .' '. Input::get('nama');
        view('main', $data);
    }
    public function insert_batch()
    {
        BackEnd::insert('actor',[
            [
                'first_name'=>'deva',
                'last_name'=>'arofi'
            ],
            [
                'first_name'=>'arba',
                'last_name'=>'karim'
            ]
        ]);
    }
}
