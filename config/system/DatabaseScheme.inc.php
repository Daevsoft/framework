<?php

class DatabaseScheme
{
    private $properties = "\n";

    public function scheme(String $table_name, Array $columns){
        $filename = get_called_class() . Key::EXT_PHP;
        $scheme_path = Indexes::$DIR_MODULES . $filename;

        if (file_exists($scheme_path)){
            $file = fopen($scheme_path, 'r+');
            $code_split = file(htmlspecialchars($scheme_path));

            foreach ($columns as $field => $column) {
                $prop = "\tpublic $". $column.";\n";
                $new_properties = array_column($code_split, $prop) != null ? STRING_EMPTY : $prop;
                $this->properties .= $new_properties;
            }
            $this->properties .= "\n";
            $codes = array();

            foreach ($code_split as $line) {
                if(string_contains('DatabaseScheme', $line)){
                    $codes[] = ($line . $this->properties);
                }else{
                    $codes[] = $line;
                };
            }

            $file_code = implode($codes);

            // Generate file decission
            fwrite($file, $file_code);
            fclose($file);
        }
        die();
    }
}
