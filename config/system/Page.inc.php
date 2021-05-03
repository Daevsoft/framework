<?php
class Page
{
    // true for testing pie cache, false for validate cache timing 
    private static $testing_cache = false;

    // for pie render
    private static $pie_source;
    public static $_filenames;
    private static $collection_temp;

    public function __construct()
    {
        
    }
    public static function __page(&$__fl=STRING_EMPTY, &$__dt = array())
    {
        self::$collection_temp = $__dt;
        self::$_filenames = dirname(dirname(__DIR__)).Key::CHAR_SLASH.config('view_path').Key::CHAR_SLASH.$__fl.'.php';
        $_filename_pie = dirname(dirname(__DIR__)).Key::CHAR_SLASH.config('view_path').Key::CHAR_SLASH.$__fl.'.pie'.'.php';
        $file_exist = true;
        
        if (!file_exists(self::$_filenames)) {
            $file_exist = false;
        }
        if(!$file_exist) {
            if (!file_exists($_filename_pie)) {
                $file_exist = false;
            }else {
                $__fl = $_filename_pie;
                self::$_filenames = $__fl;
                $file_exist = true;
            }
        }
        if(!$file_exist){
            dsSystem::MessageError('File view <b>' . self::$_filenames . '</b> not found!');
            return;
        }
        // Check is Using template or not
        if(!string_contains('.pie', $__fl)){
            // Extract All Variable
            extract($__dt);
            require(self::$_filenames);
            
        }else {
            self::render_template_alternate();
        }
    }
    private static function render_template_alternate()
    {
        // initial cache file directory
        $file_gen_enc = sha1(self::$_filenames).Key::EXT_PHP;
        $dir_cache = Indexes::$DIR_CACHE_VIEW.$file_gen_enc;
        $cache = new dsCache($dir_cache);

        // Checking cache time
        if(!$cache->exists()
        || ($cache->is_modified() 
        // || config('status') == Key::DEVELOPMENT
        ) || self::$testing_cache){
            // record into temp file
            $cache->record_file();
            // render cache into new file generate
            self::render_page($dir_cache);
        }
        $GLOBALS['FILENAMES'] = $dir_cache;
        $GLOBALS['FILENAMES_REAL'] = self::$_filenames;
        // Extract All Variable
        extract(self::$collection_temp);
        require($dir_cache);
    }
    private static function render_page(&$dir_cache)
    {
        $html = file_get_contents(self::$_filenames);
        $initialize_pie = self::pie_initialize($html);
        $initialize_syntax = self::php_initialize($initialize_pie);
        $php_cache = fopen($dir_cache,'w');
        fwrite($php_cache, $initialize_syntax);
        fclose($php_cache);
    }

    private static function pie_join($render_temp, $pie_join_precompile_temp = null)
    {
        $pie_filter_pattern = '/\@join\((.*)\)\;/iXsuUm';
        // get all string with @join
        if($pie_join_precompile_temp == null){
            // get all join text
            preg_match_all($pie_filter_pattern, $render_temp, $pie_join_precompile_temp);
        }
        // count join text
        $tab_next_pie = count($pie_join_precompile_temp[0]);
        // replace content one by one
        for ($i=0; $i < $tab_next_pie; $i++) {
            $_params_precompile = $pie_join_precompile_temp[1][$i];
            // remove end quote if exist
            $last_char = strlen($_params_precompile) - 1;
            if($_params_precompile[$last_char] == '\'')
                substr($_params_precompile, 0, $last_char);
            $render_temp = str_replace($pie_join_precompile_temp[0][$i],'<< view('.$pie_join_precompile_temp[1][$i].'); >>', $render_temp);
        }
        $pie_join_precompile_temp_next = [];
        preg_match_all($pie_filter_pattern, $render_temp, $pie_join_precompile_temp_next);
        
        return (count($pie_join_precompile_temp_next[0]) == 0) ? 
                $render_temp : Page::pie_join($render_temp, $pie_join_precompile_temp_next);
    }
    private static function pie_import($render_temp)
    {
        // get all string with @import
        $pie_import_precompile_temp = [];
        $pie_filter_pattern = '/\@import\(\'(.*)\'\s?,\s?\'(.*)\'\)/iXsuUm';
        // get all import text
        preg_match_all($pie_filter_pattern, $render_temp, $pie_import_precompile_temp);
        // count string has pie
        $tab_next_pie = count($pie_import_precompile_temp[0]);
        // get pie source[] contents
        for ($i=0; $i < $tab_next_pie; $i++) { 
            // put pie content into pie_source
            self::$pie_source[
                $pie_import_precompile_temp[2][$i]
                ] = file_get_contents(Indexes::$DIR_VIEWS.
                $pie_import_precompile_temp[1][$i].Key::EXT_PIE);
            // remove @import from view
            $render_temp = str_replace($pie_import_precompile_temp[0][$i],STRING_EMPTY, $render_temp);
        }
        for ($i=0; $i < $tab_next_pie; $i++) { 
            // fill pie part by regex ex:@comp('message')
            $rgx_pie = '/\@'.$pie_import_precompile_temp[2][$i].'\(\'(.*)\'\)/iXsuUm';
            $rgx_pie_match = [];
            preg_match_all($rgx_pie, $render_temp, $rgx_pie_match);
            $rgx_pie_count = count($rgx_pie_match[0]);
            if($rgx_pie_count > 0){
                for ($j=0; $j < $rgx_pie_count; $j++) { 
                    $rgx_pie_compile = '/\@'.$pie_import_precompile_temp[2][$i].'\(\''.
                    $rgx_pie_match[1][$j].'\'\)/i';
                    // pie source for slicing
                    $rgx_pie_source = self::$pie_source[
                        $pie_import_precompile_temp[2][$i]
                    ];
                    $rgx_source_compiled = [];
                    preg_match_all('/(?s)(?<=\@pie\(\''.$rgx_pie_match[1][$j].'\'\))(.*?)(?=\@endpie)/i',
                    $rgx_pie_source, $rgx_source_compiled);
                    $render_temp = preg_replace($rgx_pie_compile, $rgx_source_compiled[0][0], $render_temp);
                }
            }
        }
        return $render_temp;
    }
    private static function pie_initialize($render_temp)
    {
        $render_temp = self::pie_import($render_temp);
        // $render_temp = self::pie_use($render_temp);
        $render_temp = self::pie_join($render_temp);
        return $render_temp;
    }
    private static function pie_use($render_temp, $pie_use_precompile_temp = null){
        $pie_filter_pattern = '/\@use\(\'(.*)\'\)/iXsuUm';
        // get all string with @use
        if($pie_use_precompile_temp == null){
            // get all use text
            preg_match_all($pie_filter_pattern, $render_temp, $pie_use_precompile_temp);
        }
        // get views for contents

        // count use text
        $tab_next_pie = count($pie_use_precompile_temp[0]);
        if($tab_next_pie > 0){
            $file_name = Indexes::$DIR_VIEWS.$pie_use_precompile_temp[1][0];
            $file_use_path_php = $file_name.Key::EXT_PHP;
            // if view not exist use pie extension
            if(!file_exists($file_use_path_php)) $file_use_path_php = $file_name.Key::EXT_PIE;
            // put pie content into index 1
            $use_content = file_get_contents($file_use_path_php);
            // test($pie_use_precompile_temp);
            // test($file_use_path_php);
            // replace @use with view content
            // $render_temp = str_replace($pie_use_precompile_temp[0][0], $use_content, $render_temp);
            $view_part = self::pie_view($render_temp);
            // test($render_temp);
        }

        // $pie_use_precompile_temp_next = [];
        // preg_match_all($pie_filter_pattern, $render_temp, $pie_use_precompile_temp_next);
        
        // return (count($pie_use_precompile_temp_next[0]) == 0) ? 
        //         $render_temp : Page::pie_use($render_temp, $pie_use_precompile_temp_next);
    }
    public static function pie_view($render_temp)
    {
        $rgx_pie_compile = '/\@content\(\'(.*)\'\)/iXsuUm';
        // pie source for slicing
        $rgx_pie_source = $render_temp;
        $rgx_source_compiled = [];
        $r = '/(?s)(?<=\@view\(\'(.*)\'\)\n(.*?)(?=\@endview)/i';
        preg_match($r, $rgx_pie_source, $rgx_source_compiled);
        test($rgx_source_compiled);
        // $render_temp = preg_replace($rgx_pie_compile, $rgx_source_compiled[0], $render_temp);
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
                // @css
                '/\@(css)\(\'(.*)\'\)[^\n]/i',
                // @js
                '/\@(js)\(\'(.*)\'\)[^\n]/i',
                // @elseif
                '/\@(elseif)\((.*)\)\:/iXsuUm',
                // @loop and @condition
                '/\@(foreach|for|if|elseif|while)\((.*)\)\:/iXsuUm',
                // @isset
                '/\@(isset)\((.*)\)\:/iXsuUm',
                // @!isset
                '/\@(!isset)\((.*)\)\:/iXsuUm',
                // @isempty
                '/\@(isempty)\((.*)\)\:/iXsuUm',
                // @!isempty
                '/\@(!isempty)\((.*)\)\:/iXsuUm',
                // @isnull
                '/\@(isnull)\((.*)\)\:/iXsuUm',
                // @!isnull
                '/\@(!isnull)\((.*)\)\:/iXsuUm',
                // Else
                '/\@(else)/i',
                // @end loop and condition, break, endswitch
                '/\@(endforeach|endfor|endif|endwhile|endisset|!endisset|endisnull|!endisnull|!endisempty|endisempty)/iXsuUm',
                // Switch
                '/\@(switch)\((.*)\)\:/iXsuUm',
                // Case
                '/\@(case)(.*)\:/iXsuUm',
                // Default
                '/\@(default)\:/i',
                // Break Case
                '/\@(break)/s',
                '/\@(endswitch)/s'
            );
            // Replacing Index Regex
            $regex_replace = array(
                // _(( Text ))
                '<?php echo(\1); ?>',
                // @(( Text ))
                // '((\1))',
                // (! Text !)
                '<?php echo(htmlspecialchars("\1")); ?>',
                // << Syntax >>
                '<?php \1 ?>',
                // @css
                '<?php css_source(\'\2\') ?>',
                // @js
                '<?php js_source(\'\2\') ?>',
                // @elseif
                '<?php }\1(\2){ ?>',
                // @loop and @condition
                '<?php \1(\2){ ?>',
                // @isset
                '<?php if(\1(\2)){ ?>',
                // @!isset
                '<?php if(\1(\2)){ ?>',
                // @isempty
                '<?php if(STRING_EMPTY === \2){ ?>',
                // @!isempty
                '<?php if(STRING_EMPTY !== \2){ ?>',
                // @isnull
                '<?php if(NULL === \2){ ?>',
                // @!isnull
                '<?php if(NULL !== \2){ ?>',
                // Else
                '<?php }\1{ ?>',
                // @end of loop and condition, break, endswitch
                '<?php } ?>',
                // Switch
                '<?php \1(\2) : case null:; ?>\3',
                // Case
                '<?php break;\1\2 : ?> \3 ',
                // Default
                '<?php break;\1: ?>',
                // Break Case
                '<? \1; ?>',
                // Endswitch Case
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
