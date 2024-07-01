<?php
namespace Ds\Foundations\Common;

use Ds\Dir;
use Ds\Foundations\Commands\Console;

class Cache{
    public function clearAllPages()
    {
        if(!is_dir(Dir::$CACHE_VIEW)){
            mkdir(Dir::$CACHE_VIEW, 7777, true);
        }else{
            $files = glob(Dir::$CACHE_VIEW.'*');
            foreach ($files as $file) {
                if (is_file($file))
                    unlink($file);
            }
        }
        $this->resetCacheRecord();
    }
    public function resetCacheRecord()
    {
        $time_dir = Dir::$CACHE_TIME;
        if(!file_exists($time_dir)){
            mkdir(dirname($time_dir), 7777, true);
            (new File($time_dir))->create()->close();
        }
        $temp_content = file_get_contents($time_dir);
        $temp_content = "<?php\nreturn [\n];";
        file_put_contents($time_dir, $temp_content);
        Console::writeln('Cleaning cache successfully!', Console::LIGHT_GREEN);
    }
    public function clearReferences()
    {
    }
}