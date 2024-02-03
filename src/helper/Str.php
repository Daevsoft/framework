<?php

namespace Ds\Helper;

class Str
{
    public static function contains(string $text, string $find): bool
    {
        return stristr($text, $find) != false;
    }
    /**
     * Find Replace text
     *
     * @param String|Array<String> $findText
     * @param String? $replaceText
     * @return String
     **/
    public static function replace($source, $findText, $replaceText = ''){
        if(is_string($findText)){
            return str_ireplace($findText, $replaceText, $source);
        }else if(is_array($findText)){
            foreach ($findText as $find => $value) {
                $source = self::replace($source, $find, $value);
            }
            return $source;
        }
    }
    public static function empty($value){
        return $value == STRING_EMPTY;
    }
}
