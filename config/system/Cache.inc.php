<?php
class dsCache
{
    private $dir_enc_cache;
    private $modified_time;
    private $result_last_time;

    public function __construct($dir_enc_cache){
        $this->dir_enc_cache = $dir_enc_cache;
        // get modified time of file
        $this->modified_time = filemtime(Page::$_filenames);
        // Get lastest status cache
        $this->result_last_time = cache_record($this->dir_enc_cache);
    }
    public function is_modified()
    {
        return $this->result_last_time != $this->modified_time;
    }
    public function exists()
    {
        return file_exists($this->dir_enc_cache);
    }
    public function record_file()
    {
        $temp_list_item = "\t'".$this->dir_enc_cache.'\'=>\''.$this->modified_time."',";
        $temp_list_item_last = "\t'".$this->dir_enc_cache.'\'=>\''.$this->result_last_time."',";
        // Set new status cache
        $temp_content = file_get_contents(Indexes::$DIR_CACHE_TIME);
        if(string_empty($this->result_last_time)){
            $temp_content = str_replace('];', $temp_list_item."\n];", $temp_content);
        }
        else{
            $temp_content = str_replace($temp_list_item_last, $temp_list_item, $temp_content);
        }
        file_put_contents(Indexes::$DIR_CACHE_TIME, $temp_content);
    }
}