<?php
class Page
{
    // true for testing slice cache, false for validate cache timing 
    private static $testing_cache = true;

    // for slice render
    private static $slice_source;
    public static $_filenames;
    private static $collection_temp;

    public function __construct()
    {
        
    }
    public static function __page($__fl=STRING_EMPTY,$__dt = array())
    {
        self::$collection_temp = $__dt;
        self::$_filenames = dirname(dirname(__DIR__)).Key::CHAR_SLASH.config('view_path').Key::CHAR_SLASH.$__fl.'.php';
        if (!file_exists(self::$_filenames)) {
            dsSystem::MessageError('File view <b>' . $__fl . '</b> not found!');
        };
        // Check is Using template or not
        if(!string_contains('.slice', $__fl)){
            // Extract All Variable
            extract($__dt);
            require_once(self::$_filenames);
        }else {
            self::render_template($__dt);
        }
    }
    private static function render_template()
    {
        self::render_template_alternate();
    }

    private static function render_template_alternate()
    {
        // initial cache file directory
        $file_gen_md5 = md5(self::$_filenames);
        $dir_cache = Indexes::$DIR_CACHE_VIEW.$file_gen_md5;

        // Checking cache time
        if((dsCache::is_modified($file_gen_md5) || config('status') == Key::DEVELOPMENT) 
        && self::$testing_cache){
            // render cache into new file generate
            self::render_page($dir_cache, self::$collection_temp);
        }
        $GLOBALS['FILENAMES'] = $dir_cache;
        $GLOBALS['FILENAMES_REAL'] = self::$_filenames;
        // Extract All Variable
        extract(self::$collection_temp);
        require_once $dir_cache;
    }
    private static function render_page($dir_cache)
    {
        $html = file_get_contents(self::$_filenames);
        $initialize_slice = self::slice_initialize($html);
        $initialize_syntax = self::php_initialize($initialize_slice);
        $php_cache = fopen($dir_cache,'w');
        fwrite($php_cache, $initialize_syntax);
        fclose($php_cache);
    }

    private static function slice_join($render_temp)
    {
        // get all string with @join
        $slice_join_precompile_temp = [];
        $slice_filter_pattern = '/\@join\s(.*)\;/iXsuUm';
        // get all join text
        preg_match_all($slice_filter_pattern, $render_temp, $slice_join_precompile_temp);

        // count join text
        $tab_next_slice = count($slice_join_precompile_temp[0]);
        // replace content one by one
        for ($i=0; $i < $tab_next_slice; $i++) { 
            // put slice content into index 1
            $slice_join_precompile_temp[1][$i]
                = file_get_contents(Indexes::$DIR_VIEWS.
            $slice_join_precompile_temp[1][$i].Key::EXT_PHP);
            // replace @join with view content
            $render_temp = str_replace($slice_join_precompile_temp[0][$i],
            $slice_join_precompile_temp[1][$i], $render_temp);
        }

        return $render_temp;
    }
    private static function slice_import($render_temp)
    {
        // get all string with @import
        $slice_import_precompile_temp = [];
        $slice_filter_pattern = '/\@import\s(.*)\s(.*)\;/iXsuUm';
        // get all import text
        preg_match_all($slice_filter_pattern, $render_temp, $slice_import_precompile_temp);
        // count string has slice
        $tab_next_slice = count($slice_import_precompile_temp[0]);
        // get slice source[] contents
        for ($i=0; $i < $tab_next_slice; $i++) { 
            // put slice content into slice_source
            self::$slice_source[
                $slice_import_precompile_temp[2][$i]
                ] = file_get_contents(Indexes::$DIR_VIEWS.
                $slice_import_precompile_temp[1][$i].Key::EXT_SLICE);
            // remove @import from view
            $render_temp = str_replace($slice_import_precompile_temp[0][$i],STRING_EMPTY, $render_temp);
        }
        for ($i=0; $i < $tab_next_slice; $i++) { 
            // fill slice part by regex ex:@comp('message')
            $rgx_slice = '/\@'.$slice_import_precompile_temp[2][$i].'\(\'(.*)\'\)/i';
            $rgx_slice_match = [];
            preg_match_all($rgx_slice, $render_temp, $rgx_slice_match);
            $rgx_slice_count = count($rgx_slice_match[0]);
            if($rgx_slice_count > 0){
                for ($j=0; $j < $rgx_slice_count; $j++) { 
                    $rgx_slice_compile = '/\@'.$slice_import_precompile_temp[2][$i].'\(\''.
                    $rgx_slice_match[1][$j].'\'\)/i';
                    // slice source for slicing
                    $rgx_slice_source = self::$slice_source[
                        $slice_import_precompile_temp[2][$i]
                    ];
                    $rgx_source_compiled = [];
                    preg_match('/(?s)(?<=\@slice\s'.$rgx_slice_match[1][$j].'\:)(.*?)(?=\@endslice)/i',
                    $rgx_slice_source, $rgx_source_compiled);
                    $render_temp = preg_replace($rgx_slice_compile, $rgx_source_compiled[0], $render_temp);
                }
            }
        }
        return $render_temp;
    }
    private static function slice_initialize($render_temp)
    {
        $render_temp = self::slice_import($render_temp);
        $render_temp = self::slice_join($render_temp);
        return $render_temp;
    }

    private static function php_initialize($_sources){
        
            // Definition Index Regex
            $regex_pattern = array(
                // _(( Text ))
                '/\_\(\(\s(.*)\s\)\)/iXsuUm',
                // (! Text !)
                '/\(\!\s(.*)\s\!\)/iXsuUm',
                // << Syntax >>
                '/\<\<\s(.*)\s\>\>/iXsuUm',
                // @loop and @condition
                '/\@(foreach|for|if|elseif|while)\((.*)[^\n]/i',
                // Else
                '/\@(else)/i',
                // @end loop and condition, break, endswitch
                '/\@(endforeach|endfor|endif|endswitch|endwhile)/s',
                // Switch
                '/\@(switch)(.*)[^\n](\n)/i',
                // Case
                '/\@(case)(.*)[^\n](\n)/i',
                // Default
                '/\@(default)/i',
                // Break Case
                '/\@(break)/s'
            );
            // Replacing Index Regex
            $regex_replace = array(
                // (( Text ))
                '<?php echo(\1); ?>',
                // @(( Text ))
                // '((\1))',
                // (! Text !)
                '<?php echo(htmlspecialchars("\1")); ?>',
                // << Syntax >>
                '<?php \1 ?>',
                // @loop and @condition
                '<?php \1(\2{ ?>',
                // Else
                '<?php }\1{ ?>',
                // @end of loop and condition, break, endswitch
                '<?php } ?>',
                // Switch
                '<?php \1\2{\3 ',
                // Case
                ' \1\2 ?>\3',
                // Default
                '<?php \1: ?>',
                // Break Case
                '<?php \1; ?>'
            );
        // Replacing with regex
        $render_temp = preg_replace($regex_pattern, $regex_replace, $_sources);
        // return the contents
        return $render_temp;
    }
    // page not found (condition, alternate_function, argument1, argument2, ...)
    public static function not_found(bool $condition, $fun_action, ...$args)
    {
        if($condition){
            $page_not_found = (string_empty(config('404_not_found_file')) ? Key::INDEX : config('404_not_found_file'));
            // call view page
            FrontEnd::page('404'.Key::CHAR_SLASH. $page_not_found);
            die();
        }else{
            $fun_action($args);
        }
    }
}
