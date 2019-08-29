<?php
class dsCache
{

    public function __construct(){
    }
    
    // Array $cache_request
    public static function record_cache($cache_key,$cache_request)
    {
    }

    public static function is_modified($dir_md5_cache)
    {
        // get modified time of file
        $modified_time = filemtime(Page::$_filenames);
        // Get lastest status cache
        $result_last_time = cache_record($dir_md5_cache);
        $is_modified_cache = ($result_last_time != $modified_time);
        if($is_modified_cache){
            $temp_list_item = "\t'".$dir_md5_cache.'\'=>\''.$modified_time."',";
            $temp_list_item_last = "\t'".$dir_md5_cache.'\'=>\''.$result_last_time."',";
            // Set new status cache
            $temp_content = file_get_contents(Indexes::$DIR_CACHE_TIME);
            if(string_empty($result_last_time)){
                $temp_content = str_replace('];', $temp_list_item."\n];", $temp_content);
            }
            else{
                $temp_content = str_replace($temp_list_item_last, $temp_list_item, $temp_content);
            }
            file_put_contents(Indexes::$DIR_CACHE_TIME, $temp_content);
        }
        return $is_modified_cache;
    }
}