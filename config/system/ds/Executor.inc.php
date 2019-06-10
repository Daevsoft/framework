<?php
class Executor extends Ds
{
    public function __construct() {
    }
    public function Commands($_args)
    {
        // Get Command params
        $_command = $_args[FIRST_ARG];
        $_ArgValue = '';
        // Get parameter choose command
        // Example 'make:controller' will get 'controller' only
        if($this->IsContain($_command,':'))
            $_ArgValue = $this->GetArgValue($_command);
        switch ($_command) {
            case $this->IsContain($_command,'run'):
            Server::Run($_ArgValue, $_args);
                break;
            case $this->IsContain($_command,COMMAND_ADD):
                Manager::Structure(COMMAND_ADD,$_ArgValue);
                break;
            case $this->IsContain($_command,COMMAND_DEL):
                Manager::Structure(COMMAND_DEL,$_ArgValue);
                break;
            case $this->IsContain($_command,COMMAND_RESTORE):
                Manager::Structure(COMMAND_RESTORE,$_ArgValue);
                break;
            default:
                msg($_command.' is not command !');
            break;
        }
    }

    public static function View($_command)
    {
        // Filename
        $filenames = get_command(SECOND_ARG);
        $files = explode(',',$filenames);
        switch ($_command) {
            case COMMAND_ADD:
                self::CreateView($files);
                break;
            default:
                break;
        }
    }
    public static function Api($_command)
    {
        // Filename
        $filenames = get_command(SECOND_ARG);
        $files = explode(',',$filenames);

        switch ($_command) {
            case COMMAND_ADD:
                self::CreateApi($files, $mtd);
                break;
            case COMMAND_DEL:
                self::DeleteFile($files, APIS, STRING_EMPTY, FALSE);        
                break;
            case COMMAND_RESTORE:
                self::DeleteFile($files, APIS, STRING_EMPTY, TRUE);            
                break;
        }
    }
    public static function Controller($_command)
    {
        // Filename
        $filenames = get_command(SECOND_ARG);
        $files = explode(',',$filenames);
        switch ($_command) {
            case COMMAND_ADD:
                self::CreateController($files);
                break;
            case COMMAND_DEL:
                self::DeleteFile($files, CONTROLLERS, CONTROLLER, FALSE);
                break;
            case COMMAND_RESTORE:
                self::DeleteFile($files, CONTROLLERS, CONTROLLER, TRUE);
                break;
            
            default:
                break;
        }
    }
    
    public static function Model($_command)
    {
        // Filename
        $filenames = get_command(SECOND_ARG);
        $files = explode(',',$filenames);

        switch ($_command) {
            case COMMAND_ADD:
                self::CreateModel($files);
                break;
            case COMMAND_DEL:
                self::DeleteFile($files, MODELS, MODEL, FALSE);
                break;
            case COMMAND_RESTORE:
                self::DeleteFile($files, MODELS, MODEL, TRUE);
                break;
        }
    }
    public static function CreateView($_files)
    {
        foreach ($_files as $file) {
            $_filenames  = $file;
            if(strstr($file, '.slice') != STRING_EMPTY)
                $source = '_(( "'.$file.' created !" ))';
            else
                $source = '<?php echo "'.$file.' created !"; ?>';
            self::CreateFile($_filenames, VIEWS, VIEW, $source);
        }
    }
    public static function CreateApi($_files, $method)
    {
        $mtd = count(get_command()) == 4 ? substr(get_command(THIRD_ARG), 1) : 'request';
        foreach ($_files as $file) {
            $_filenames  = $file;
            $source = '<?php 
Api::register(\''.$file.'\'); 

Api::'.$mtd.'(\'/\', function($_req, $sql){
    return [\'response\' => \'Created\'];
});';
            self::CreateFile($_filenames, APIS, API, $source);
        }
    }
    public static function CreateController($_files)
    {
        foreach ($_files as $file) {
            $_filenames  = ucfirst(strtolower($file)).CONTROLLER;
            $source = '<?php
/**
 * '.$_filenames.'
 */
class '.$_filenames.' extends dsController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        $data = array(
            \'demoVariable\' 		=> "this is sample text variable"
        );
        FrontEnd::page(\''.$file.'\',$data);
    }

    public function demoSlice()
    {
        $data = array(
            \'demoVariable\'       => "Demo Variable"
        );
        FrontEnd::page(\''.$file.'.slice\',$data);
    }
}';
            self::CreateFile($_filenames, CONTROLLERS, CONTROLLER, $source);
        }
    }
    public static function CreateModel($_files)
    {
        foreach ($_files as $file) {
            $_filenames  = ucfirst(strtolower($file)).Key::MODEL;
            $source = '<?php
/**
 * '.$_filenames.'
 */
class '.$_filenames.' extends dsModel
{
    public function __construct()
    {
    }
    
    public function index()
    {
    }

    // Demo function
    public function getData()
    {
        return $this->select(\''.$_file.'\')::get_all();
    }
}';
            self::CreateFile($_filenames, MODELS, MODEL, $source);
        }
    }
    public static function DeleteFile($_files, $folder, $additionalEnd, $_restore = FALSE)
    {
        if(is_array($_files)){
            foreach ($_files as $file) {
                $_filenames  = ucfirst(strtolower($file)). $additionalEnd.'.php';
                self::RemoveFileTemporary($_filenames, $folder, $_restore);
            }
        }else{
            $_filenames  = ucfirst(strtolower($_files)). $additionalEnd.'.php';
            self::RemoveFileTemporary($_filenames, $folder, $_restore);
        }
    }
    public static function RemoveFileTemporary($_filenames, $_target_directory, $_restore)
    {
        $filenames = '/'.$_filenames;
        $new_directory = TRASH_DIR.$_target_directory.$filenames;
        $directory = MAIN_DIR.'/app'.$_target_directory.$filenames;
        if(!$_restore){
            rename($directory, $new_directory);
            msg($_filenames.' has been removed!');
        }else{
            if (!file_exists($new_directory)) {
                msg($_filenames.' not available on storage.');
            }else{
                rename($new_directory, $directory);
                msg($_filenames.' restored!');
            }
        }
    }
    public static function CreateFile($filename, $folder, $type, $source)
    {
        $filename = ucfirst($filename);
        $directory = MAIN_DIR.'/app'.$folder.'/'.$filename. '.php';
        if (file_exists($directory)) {
            msg('File ' . $filename . ' was exist.');
            $read = read('Replace it (y/n) ?');
            if($read == 'y'){
                echo $filename;
                self::DeleteFile($filename, $folder, STRING_EMPTY, FALSE);
            }else{
                msg('failed to create '.$filename.' !'."\n");
                return;
            }
        }
        // Generate file decission
        $file = fopen($directory, 'w');
        fwrite($file, $source);
        fclose($file);
        msg($filename.' has been successfully created!'."\n");
    }
}
