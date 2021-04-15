<?php
class Cache{
    public function clearAllPages()
    {
        $files = glob(CACHE_DIR.'/views/*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
        $this->resetCacheRecord();
    }
    public function resetCacheRecord()
    {
        $time_dir = CACHE_DIR.'/times/temp';
        $temp_content = file_get_contents($time_dir);
        $temp_content = "<?php\nreturn [\n];";
        file_put_contents($time_dir, $temp_content);
    }
    public function clearReferences()
    {
        $ref_dir = CACHE_DIR.'/object/ref';
        $temp_ref = file_get_contents($ref_dir);
        $temp_ref = '';
        file_put_contents($ref_dir, $temp_ref);
    }
}