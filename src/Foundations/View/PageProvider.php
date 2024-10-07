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
    private $slot_var_name = '__slotComponents';
    private $testing_cache = false;
    private static PageProvider $instance;

    // for pie render
    private $pie_source;
    public $_filenames;
    private $collection_temp;

    public static function getInstance()
    {
        return self::$instance;
    }

    public function install()
    {
        self::$instance = $this;
    }
    public function run() {}
    public static function init()
    {
        return self::$instance;
    }
    public function viewFileName($filename) {}
    public function __page($__fl = STRING_EMPTY, $__dt = array(), $slot = null)
    {
        try {
            $this->collection_temp = $__dt;
            $this->_filenames = Dir::$VIEWS . $__fl . '.php';
            $_filename_pie = Dir::$VIEWS . $__fl . '.pie' . '.php';
            $file_exist = true;

            if (!file_exists($this->_filenames)) {
                $file_exist = false;
            }
            if (!$file_exist) {
                if (!file_exists($_filename_pie)) {
                    $file_exist = false;
                } else {
                    $__fl = $_filename_pie;
                    $this->_filenames = $__fl;
                    $file_exist = true;
                }
            }
            if (!$file_exist) {
                throw new Exception('File view <b>' . $this->_filenames . '</b> not found!');
            }
            // Check is Using template or not
            if (!Str::contains($__fl, '.pie')) {
                // Extract All Variable
                extract($__dt);
                // $__slotComponents = new Slot($slot);
                require $this->_filenames;
            } else {
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
        // dd($this->_filenames);
        // $file_gen_enc = sha1($this->_filenames) . '.php';
        //Dir::$CACHE_VIEW . $file_gen_enc;
        $cache = new CacheView($this->_filenames);
        $dir_cache = Dir::$CACHE_VIEW . $cache->encryptedFile;
        // Checking cache time
        if (
            !$cache->exists()
            || ($cache->is_modified()
                // || env('status') == Key::DEVELOPMENT
            ) || $this->testing_cache
        ) {
            // record into temp file
            $cache->recordViewTime();
            // render cache into new file generate
            $this->render_page($dir_cache);
        }
        // Extract All Variable
        extract($this->collection_temp);
        require $dir_cache;
    }
    private function renderContents(string $html)
    {
        $html = $this->slot_initialize($html);
        $initialize_pie = $this->pie_initialize($html);
        $initialize_pie = ' ' . $initialize_pie;
        $initialize_syntax = $this->php_initialize($initialize_pie);
        return $initialize_syntax;
    }
    private function render_page(&$dir_cache)
    {
        $html = file_get_contents($this->_filenames);
        $resultRender = $this->renderContents($html);
        $php_cache = fopen($dir_cache, 'w');
        fwrite($php_cache, $resultRender);
        fclose($php_cache);
    }
    private function renderUsePie($content)
    {
        $matchesUse = [];
        preg_match_all('/\@(use\(\'(.*)\'\))/iXsuUm', $content, $matchesUse);
        $usesFilename = $matchesUse[2]; // filename
        $usesTarget = $matchesUse[0]; // @use(...)
        foreach ($usesTarget as $i => $value) {
            $fileContent = file_get_contents(Dir::$VIEWS . '/' . $usesFilename[$i] . '.pie.php');
            $content = Str::replace($content, $value, $fileContent);
        }
        if (strstr($content, '@use(')) {
            return $this->renderUsePie($content);
        }
        return $content;
    }
    private function slot_initialize(&$content)
    {
        $content = $this->renderUsePie($content);
        $rgx_source_compiled = [];
        preg_match_all(
            '/\@part\(\'(.*)\'\)(.*)(?<=\@endpart)/iXsuUm',
            $content,
            $rgx_source_compiled
        );
        $slotCount = count($rgx_source_compiled[0]);
        if ($slotCount > 0) {
            for ($i = 0; $i < $slotCount; $i++) {
                $rgx_content = $rgx_source_compiled[0][$i];
                $rgx_key = $rgx_source_compiled[1][$i];
                $rgx_body = $rgx_source_compiled[2][$i];
                $rgx_body = Str::replace($rgx_body, '@endpart', '');

                $content = preg_replace('/\@(slot\(\'' . $rgx_key . '\'\))/iXsuUm', $rgx_body, $content);
                $content = Str::replace($content, $rgx_content, '');
            }
        }
        $content = preg_replace('/\@(slot\(\'.*\'\))/iXsuUm', '', $content);
        return $content;
    }

    private function pie_components($render_temp, $pie_precomponent_temp = null)
    {
        $pie_filter_pattern = '/<x-(\w+[\w.-]*)([^>]*)([^>]*?)>(.*)<\/x-(\1)>/iXsuUm';
        // get all string with @join
        if ($pie_precomponent_temp === null) {
            // get all join text
            preg_match_all($pie_filter_pattern, $render_temp, $pie_precomponent_temp);
        }

        $results = [];
        foreach ($pie_precomponent_temp[0] as $index => $match) {
            $raw = $pie_precomponent_temp[0][$index]; // Tag name
            $tagName = $pie_precomponent_temp[1][$index]; // Tag name
            $attributesString = trim($pie_precomponent_temp[3][$index]); // Attributes string
            $innerContent = trim($pie_precomponent_temp[4][$index]); // Inner content
            // Parse attributes into an associative array
            $attributes = [];
            preg_match_all('/(\w+)\s*=\s*"([^"]*)"/iXsuUm', $attributesString, $attrMatches);

            foreach ($attrMatches[1] as $attrIndex => $attrName) {
                $attributes[$attrName] = $attrMatches[2][$attrIndex];
            }
            $resultComponent = $this->renderComponent($raw, $tagName, $attributes, $innerContent);
            // Store the result
            $render_temp = str_replace($raw, $resultComponent, $render_temp);
        }
        $pie_precomponent_temp_next = [];
        preg_match_all($pie_filter_pattern, $render_temp, $pie_precomponent_temp_next);

        return (count($pie_precomponent_temp_next[0]) == 0) ?
            $render_temp : $this->pie_join($render_temp, $pie_precomponent_temp_next);
    }

    private function renderComponent($raw, $tagName, $attributes, $innerContent)
    {
        $attrResult = [];
        foreach ($attributes as $attrName => $attrValue) {
            if ($attrName[0] == ':') {
                $attrResult[] = '\'' . $attrName . '\'=>' . $attrValue . '';
            } else {
                $attrResult[] = '\'' . $attrName . '\'=>\'' . $attrValue . '\'';
            }
        }
        $attr = '[';
        $attr .= implode(',', $attrResult);
        $attr .= ']';

        $resultRender = $this->renderContents($innerContent);

        $html = file_get_contents(Dir::$VIEWS . View::filename($tagName) . '.pie.php');
        $slot_list = null;
        preg_match_all('/<x-slot(?:\s+name="([^"]*)")?\s*\/?>/iXsuUm', $html, $slot_list);
        // Create $part to fill the slot
        $parts = [];
        // if empty, fill slot with resultRender or default innerContent
        if ($slot_list[0]) {
            $slotRaw = $slot_list[0];
            $slotName = $slot_list[1];
            $lenSlot = count($slotRaw);
            for ($i = 0; $i < $lenSlot; $i++) {
                $slot = $slotRaw[$i];
                $slotKey = $slotName[$i];
                if ($slotKey == '') {
                    $slotKey = 'default';
                }
                $slotRender = ('<?= $' . $this->slot_var_name . '->getSlot(\'' . $slotKey . '\') ?>');
                $html = str_ireplace($slot, $slotRender, $html);
                $parts[$slotKey] = 'fn() => ()';
            }
        }
        dd($resultRender, $html);

        dd('<?php view(\'' . $tagName . '\', ' . $attr . ') ?>');
        dd($raw, $tagName, $attrResult, $innerContent);
    }

    private function pie_join($render_temp, $pie_join_precompile_temp = null)
    {
        $pie_filter_pattern = '/\@join\((.*)\)[^\)]/iXsuUm';
        // get all string with @join
        if ($pie_join_precompile_temp === null) {
            // get all join text
            preg_match_all($pie_filter_pattern, $render_temp, $pie_join_precompile_temp);
        }
        // count join text
        $tab_next_pie = count($pie_join_precompile_temp[0]);
        // replace content one by one
        for ($i = 0; $i < $tab_next_pie; $i++) {
            $_params_precompile = $pie_join_precompile_temp[1][$i];
            // remove end quote if exist
            $last_char = strlen($_params_precompile) - 1;
            if ($_params_precompile[$last_char] == '\'') {
                substr($_params_precompile, 0, $last_char);
            }

            $render_temp = str_replace($pie_join_precompile_temp[0][$i], '<?php view(' . $pie_join_precompile_temp[1][$i] . '); ?>', $render_temp);
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
        for ($i = 0; $i < $tab_next_pie; $i++) {
            // put pie content into pie_source
            $this->pie_source[$pie_import_precompile_temp[2][$i]] = file_get_contents(Dir::$VIEWS .
                $pie_import_precompile_temp[1][$i] . '.pie.php');
            // remove @import from view
            $render_temp = str_replace($pie_import_precompile_temp[0][$i], STRING_EMPTY, $render_temp);
        }
        for ($i = 0; $i < $tab_next_pie; $i++) {
            // fill pie part by regex ex:@comp('message')
            $rgx_pie = '/\@' . $pie_import_precompile_temp[2][$i] . '\(\'(.*)\'\)/iXsuUm';
            $rgx_pie_match = [];
            preg_match_all($rgx_pie, $render_temp, $rgx_pie_match);
            $rgx_pie_count = count($rgx_pie_match[0]);
            if ($rgx_pie_count > 0) {
                for ($j = 0; $j < $rgx_pie_count; $j++) {
                    $rgx_pie_compile = '/\@' . $pie_import_precompile_temp[2][$i] . '\(\'' .
                        $rgx_pie_match[1][$j] . '\'\)/i';
                    // pie source for slicing
                    $rgx_pie_source = $this->pie_source[$pie_import_precompile_temp[2][$i]];
                    $rgx_source_compiled = [];
                    preg_match_all(
                        '/(?s)(?<=\@pie\(\'' . $rgx_pie_match[1][$j] . '\'\))(.*?)(?=\@endpie)/i',
                        $rgx_pie_source,
                        $rgx_source_compiled
                    );
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
        // $render_temp = $this->pie_components($render_temp);
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

    private function php_initialize($_sources)
    {
        // Definition Index Regex
        $regex_pattern = array(
            // {{  Text }}
            '/(.*[^\@])\{\{(.*)\}\}/iXsuUm',
            // {! Text !}
            '/\{\!\s(.*)\s\!\}/iXsuUm',
            // << Syntax >>
            '/\<\<\s(.*)\s\>\>/iXsuUm',
            // @css
            '/\@(css)\(\'(.*)\'\)[^\n]/iXsuUm',
            // @js
            '/\@(js)\(\'(.*)\'(\,)?\s?((\'.*\')?|(\[.*\])?|(\".*\")?)?\)[^\n]/iXsuUm',
            // @elseif
            '/\@(elseif)\((.*)\)\:/iXsuUm',
            // @loop and @condition
            '/\@(foreach|for|if|elseif|while)\((.*)\)\:/iXsuUm',
            // @isset
            '/\@(isset)\((.*)\)\:/iXsuUm',
            // @notset
            '/\@(notset)\((.*)\)\:/iXsuUm',
            // @empty
            '/\@(empty)\((.*)\)\:/iXsuUm',
            // @notempty
            '/\@(notempty)\((.*)\)\:/iXsuUm',
            // @isnull
            '/\@(isnull)\((.*)\)\:/iXsuUm',
            // @notnull
            '/\@(notnull)\((.*)\)\:/iXsuUm',
            // Else
            '/\@(else)/i',
            // @end loop and condition, break, endswitch
            '/\@(endforeach|endfor|endif|endwhile|endisset|endisflash|endauth|endnotset|endisnull|endnotnull|endempty|endnotempty)/iXsuUm',
            // Switch
            '/\@(switch)\((.*)\)\:/iXsuUm',
            // Case
            '/\@(case)(.*)\:/iXsuUm',
            // Default
            '/\@(default)\:/iXsuUm',
            // Break Case
            '/\@(break)/s',
            '/\@(endswitch)/s',
            // Continue
            '/\@(continue)/s',
            // @isflash
            '/\@(isflash)\((.*)\)\:/iXsuUm',
            // @flash
            '/\@(flash)\((.*)\)/iXsuUm',
            // Csrf
            '/\@(csrf)/iXsuUm',
            // @error flash
            '/\@(error)\((.*)\)/iXsuUm',
            // @auth
            '/\@(auth)/iXsuUm',
            '/\@old\((.*)\)/',
        );
        // Replacing Index Regex
        $regex_replace = array(
            // {{ Text }}
            '\1<?php echo(\2) ?>',
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
            // @notset
            '<?php if(!isset(\2)){ ?>',
            // @isempty
            '<?php if(empty(\2)){ ?>',
            // @notempty
            '<?php if(!empty(\2)){ ?>',
            // @isnull
            '<?php if(\2 === NULL){ ?>',
            // @!isnull
            '<?php if( \2 !== NULL){ ?>',
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
            // Continue
            '<? \1; ?>',
            // @isflash
            '<?php if(is_flash(\2)){ ?>',
            // Flash
            '<?= \1(\2) ?>',
            // CSRF
            '<input type="hidden" name="csrf_token" value="<?= Ds\Foundations\Security\Csrf::token() ?>">',
            // Error Flash
            '<?= flash(\'error_\'.\2) ?>',
            // Auth
            '<?php if(session(\'user\', false)){ ?>',
            // Flash
            '<?= old(\1) ?>',
        );
        // Replacing with regex
        $render_temp = preg_replace($regex_pattern, $regex_replace, $_sources);
        // Ignore {{  }}
        $render_temp = preg_replace('/\@\{\{(.*)\}\}/iXsuUm', '{{\1}}', $render_temp);
        // return the contents
        return $render_temp;
    }
    // page not found (condition, alternate_function, argument1, argument2, ...)
    public function not_found(bool $condition, $fun_action, ...$args)
    {
        if ($condition) {
            $page_not_found = (empty(Env::get('404_VIEW')) ? 'index' : Env::get('404_VIEW'));
            // call view page
            die('404 Page Not Found');
        } else {
            $fun_action($args);
        }
    }
    public static function page_not_found()
    {
        die('404 Page Not Found');
    }
}
