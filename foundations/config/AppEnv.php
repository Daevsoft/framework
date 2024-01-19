<?php

namespace Ds\Foundations\Config;

use Ds\Dir;
use Ds\Helper\File;

class AppEnv {
    private $envfile;
    static function create($envFile){
        return new AppEnv($envFile);
    }
    public function __construct($_envfile) {
        $this->envfile = $_envfile;
        $this->generateConfig();
    }
    function generateConfig(){
        $env = file($this->envfile);
        $envData = "<?php\n\$config=[];\n";

        foreach ($env as $line) {
            if(trim($line) != ''){
                [ $key, $value ] = explode('=', $line);
                $envData .= "\$config['".trim($key)."']='".trim($value)."';\n";
            }
        }
        $envData .= "\n\$GLOBALS['CACHE_CONFIG'] = \$config;";

        $config_temp = Dir::$CONFIG_TEMP;
        $objFile = new File($config_temp);
        $objFile->rewrite($envData)->close();
    }
}