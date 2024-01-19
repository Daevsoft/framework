<?php
namespace Ds\Foundations\Common;

use Ds\Dir;

class Cache{
    public function clearAllPages()
    {
        $files = glob(Dir::$CACHE_VIEW.'*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
        $this->resetCacheRecord();
    }
    public function resetCacheRecord()
    {
        $time_dir = Dir::$CACHE_TIME;
        $temp_content = file_get_contents($time_dir);
        $temp_content = "<?php\nreturn [\n];";
        file_put_contents($time_dir, $temp_content);
    }
    public function clearReferences()
    {
    }
}