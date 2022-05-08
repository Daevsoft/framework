<?php

class Session  
{
    public function clear()
    {
        $files = glob(SESSION_DIR.'/*'); // get all file namespace
        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }
        echo "Session Cleared !\n";
    }
}