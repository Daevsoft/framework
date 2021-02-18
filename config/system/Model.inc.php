<?php
class Model extends dsModel
{
    protected $table_name = NULL;
    private $table_name_col = NULL;
    private $table_child = [];
    protected $sql;

    public function __construct() {
        $this->table_name_col = $this->to_snake(get_called_class());
        $this->table_name = string_condition($this->table_name, $this->table_name_col);
        $this->sql = 'SELECT * from '.$this->table_name;
    }
    public function to_snake($str)
    {
        return strtolower(preg_replace('/([A-Z])(.*)([A-Z])/','\1\2_\3', $str));
    }
    public function belong($class, $fk = null, $pk = null)
    {
        Load::inc_model($class);
        $ins = new $class;
        $table = $ins->getName();
        $from = $this->table_name;
        
        $fk = string_condition($fk,  $this->table_name_col.'_id');
        $pk = string_condition($pk, 'id');


        $this->sql .= ' LEFT JOIN '.$table.' ON '.$from.'.'.$pk.'='.$table.'.'.$fk;
        $ins->sql = $this->sql;

        return $ins;
    }
    public function getName()
    {
        return $this->table_name;
    }
    public function get()
    {
        // return $this->sql;
        return $this->query($this->sql)->get_assoc();
    }
    public static function child($parent, $fk = NULL, $pk = NULL)
    {
        $aa = get_called_class();
        $parent->belong($aa);
    }
    public function save()
    {
        $bb = get_called_class();
        echo $bb;
        return get_class_vars(get_class(($this)));
    }
}
