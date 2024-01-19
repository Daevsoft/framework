<?php

namespace Ds\Foundations\View;

use Ds\Dir;
use Ds\Foundations\Config\Env;
use Ds\Foundations\Exceptions\dsException;
use Ds\Foundations\Provider;
use Ds\Helper\Str;
use Exception;

use function Ds\Base\App\Config\env;


class PageProvider implements Provider
{
    // true for testing pie cache, false for validate cache timing 
    private $testing_cache = false;

    // for pie render
    private $pie_source;
    public $_filenames;
    private $collection_temp;

    public function install()
    {
    }
    public function run()
    {
    }
    public function __page(&$__fl=STRING_EMPTY, &$__dt = array())
    {
        try {
            $this->collection_temp = $__dt;
            $this->_filenames = Dir::$VIEWS.$__fl.'.php';
            $_filename_pie = Dir::$VIEWS.$__fl.'.pie'.'.php';
            $file_exist = true;
            
            if (!file_exists($this->_filenames)) {
                $file_exist = false;
            }
            if(!$file_exist) {
                if (!file_exists($_filename_pie)) {
                    $file_exist = false;
                }else {
                    $__fl = $_filename_pie;
                    $this->_filenames = $__fl;
                    $file_exist = true;
                }
            }
            if(!$file_exist){
                throw new Exception('File view <b>' . $this->_filenames . '</b> not found!');
            }
            // Check is Using template or not
            if(!Str::contains($__fl, '.pie')){
                // Extract All Variable
                extract($__dt);
                require($this->_filenames);
                
            }else {
                $this->render_template_alternate();
            }
        } catch (Exception $ex) {
            $ds = new dsException($ex);
            $ds->show_exception(true);
            die();
        }
    }
    private function render_template_alternate()
    {
        // initial cache file directory
        $file_gen_enc = sha1($this->_filenames).'.php';
        $dir_cache = Dir::$CACHE_VIEW.$file_gen_enc;
        $cache = new CacheView($dir_cache, $this->_filenames);
        // Checking cache time
        if(!$cache->exists()
        || ($cache->is_modified() 
        // || env('status') == Key::DEVELOPMENT
        ) || $this->testing_cache){
            // record into temp file
            $cache->record_file();
            // render cache into new file generate
            $this->render_page($dir_cache);
        }
        $GLOBALS['FILENAMES'] = $dir_cache;
        $GLOBALS['FILENAMES_REAL'] = $this->_filenames;
        // Extract All Variable
        extract($this->collection_temp);
        require($dir_cache);
    }
    private function render_page(&$dir_cache)
    {
        $html = file_get_contents($this->_filenames);
        $this->slot_initialize($html);
        $initialize_pie = $this->pie_initialize($html);
        $initialize_syntax = $this->php_initialize($initialize_pie);
        $php_cache = fopen($dir_cache,'w');
        fwrite($php_cache, $initialize_syntax);
        fclose($php_cache);
    }
    private function slot_initialize(&$content){
        // test($content);
        // '/\@(slot\(\'(.*)\'\))/iXsuUm',
        // @use
        $matchesUse = [];
        preg_match_all('/\@(use\(\'(.*)\'\))/iXsuUm', $content, $matchesUse);
        // if(count($matches) > 0)
        $usesFilename = $matchesUse[2]; // filename
        $usesTarget = $matchesUse[0]; // @use(...)
        foreach ($usesTarget as $i => $value) {
            $fileContent = file_get_contents(Dir::$VIEWS.'/'.$matchesUse[2][$i].'.pie.php');
            $content = Str::replace($content, $value, $fileContent);
        }
        
        $rgx_source_compiled = [];
        preg_match_all('/\@part\(\'(.*)\'\)(.*)(?<=\@endpart)/iXsuUm',
        $content, $rgx_source_compiled);
        $slotCount = count($rgx_source_compiled[0]);
        if ($slotCount > 0) {
            for ($i=0; $i < $slotCount; $i++) {
                $rgx_content = $rgx_source_compiled[0][$i];
                $rgx_key = $rgx_source_compiled[1][$i];
                $rgx_body = $rgx_source_compiled[2][$i];
                $rgx_body = Str::replace($rgx_body, '@endpart', '');
                
                $content = preg_replace('/\@(slot\(\''.$rgx_key.'\'\))/iXsuUm', $rgx_body, $content);
                $content = Str::replace($content, $rgx_content, '');
            }
        }
        $content = preg_replace('/\@(slot\(\'.*\'\))/iXsuUm', '', $content);
    }

    private function pie_join($render_temp, $pie_join_precompile_temp = null)
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
                $render_temp : $this->pie_join($render_temp, $pie_join_precompile_temp_next);
    }
    private function pie_import($render_temp)
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
            $this->pie_source[
                $pie_import_precompile_temp[2][$i]
                ] = file_get_contents(Dir::$VIEWS.
                $pie_import_precompile_temp[1][$i].'.pie.php');
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
                    $rgx_pie_source = $this->pie_source[
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
    private function pie_initialize($render_temp)
    {
        $render_temp = $this->pie_import($render_temp);
        $render_temp = $this->pie_join($render_temp);
        return $render_temp;
    }
    public function pie_view($render_temp)
    {
        $rgx_pie_compile = '/\@content\(\'(.*)\'\)/iXsuUm';
        // pie source for slicing
        $rgx_pie_source = $render_temp;
        $rgx_source_compiled = [];
        $r = '/(?s)(?<=\@view\(\'(.*)\'\)\n(.*?)(?=\@endview)/i';
        preg_match($r, $rgx_pie_source, $rgx_source_compiled);
        $render_temp = preg_replace($rgx_pie_compile, $rgx_source_compiled[0], $render_temp);
    }

    private function php_initialize($_sources){
            // Definition Index Regex
            $regex_pattern = array(
                // {{  Text }}
                '/\{\{\s(.*)\s\}\}/iXsuUm',
                // {! Text !}
                '/\{\!\s(.*)\s\!\}/iXsuUm',
                // << Syntax >>
                '/\<\<\s(.*)\s\>\>/iXsuUm',
                // @css
                '/\@(css)\(\'(.*)\'\)[^\n]/i',
                // @js
                '/\@(js)\(\'(.*)\'(\,)?\s?((\'.*\')?|(\[.*\])?|(\".*\")?)?\)[^\n]/i',
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
                '/\@(endswitch)/s',
                // @slot
                // '/\@(slot\(\'(.*)\'\))/iXsuUm',
                // @use
                // '/\@(use\(\'(.*)\'\))/iXsuUm',
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
                '<?php js_source(\'\2\'\3\4\5) ?>',
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
                '<?php \1; ?>',
                // Slot
                // Use
                //
            );
        // Replacing with regex
        $render_temp = preg_replace($regex_pattern, $regex_replace, $_sources);
        // return the contents
        return $render_temp;
    }
    // page not found (condition, alternate_function, argument1, argument2, ...)
    public function not_found(bool $condition, $fun_action, ...$args)
    {
        if($condition){
            $page_not_found = (empty(Env::get('404_VIEW')) ? 'index' : Env::get('404_VIEW'));
            // call view page
            // RouteProvider::page('404/'. $page_not_found);
            die();
        }else{
            $fun_action($args);
        }
    }
}
