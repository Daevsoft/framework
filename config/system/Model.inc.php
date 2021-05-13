<?php
interface BaseModel
{
    function __construct();
    // function save();
}

class Model extends dsModel implements BaseModel
{
    protected $table_name = '';
    private $table_child = [];
    protected $sql;
    protected $obj_class;

    public function __construct() {
        $this->table_name = $this->to_snake(get_called_class());
        $this->sql = 'SELECT * from '.$this->table_name;
        $this->obj_class = $this;
    }
    public function to_snake($str)
    {
        return strtolower(preg_replace('/([A-Z])(.*)([A-Z])/','\1\2_\3', $str));
    }
    public function belong($class, $fk = null, $pk = null)
    {
        Load::inc_model($class);
        $ins = new $class;
        
        $fk = string_condition($fk, $ins->getName().'_id');
        $pk = $ins->getName().'.'. string_condition($pk, 'id');

        $from = $this->obj_class->table_name;
        $this->sql .= ' JOIN '.$ins->getName().' ON '.$pk.'='.$from.'.'.$fk;
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
    public function child($relate_class, $fk, $pk)
    {
        $aa = new $relate_class;
        $this->obj_class->belong($aa);
        // desc($parent);
    }
    // public function save()
    // {
    //     $result = [];
    //     // $aa = get_called_class();
    //     $class_vars = (get_class_vars(get_class($this->obj_class)));
    //     $result = get_object_vars($this->obj_class);
    //     $result = $this->array_diff($result, $class_vars);
    //     desc($result);
    // }
    public function array_diff($arr1, $arr2)
    {
        $arr1_len = count($arr1);
        $arr2_len = count($arr1);
        $arr_one = $arr_two = [];
        $arr_range = 0;
        if ($arr1_len > $arr2_len) {
            $arr_range = $arr1_len;
            $arr_one = $arr1;
            $arr_two = $arr2;
        } else {
            $arr_range = $arr2_len;
            $arr_one = $arr2;
            $arr_two = $arr1;
        }
        $arr1_keys = array_keys($arr_one);
        $arr_start = count($arr_two) - 1;
        $result = [];
        for ($i=$arr_start; $i < $arr_range; $i++) { 
            // if($arr1_keys[$i]){
                echo $i.' ';
                // $result[$arr1_keys[$i]] = $arr_one[$arr1_keys[$i]];
            // }
        }
        return $result;
    }
}