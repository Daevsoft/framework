<?php
class HTMLHelper
{
    public function __construct()
    {
    }
}

class HTML
{
    private static $helper;
    public function __construct()
    {
    }
    public static function form($action, $_method = 'POST', $_className = '' , $file_input = FALSE)
    {
      return '<form '.($_className == '' ? '' : 'class=\''.$_className.'\'').' action=\''.site($action).'\' method=\'POST\' '.
      ($file_input ? 'enctype=\'multipart/form-data\'' : '' ).'>';
    }
    public static function label($text, $_className = '')
    {
      return '<label class=\''.$_className.'\'>'.$text.'</label>';
    }
    public static function form_end()
    {
      return '</form>';
    }
    public static function a($href = '#', $teks='link', $_className = '')
    {
      return '<a href="'.site($href).'" '.self::echo_null('class', $_className).'>'.$teks.'</a>';
    }
    public static function input($_inputType, $_inputName , $_className, $_value , $_attribute){
      return '<input type=\''.$_inputType.'\' '.($_inputName == '' ? '' : 'name=\''.$_inputName.'\'').' '
      .($_className == '' ? '' : 'class=\''.$_className.'\'').' value=\''.$_value.'\' '.$_attribute.'>';
    }
    public static function input_text($_inputName = 'inputText', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('text', $_inputName, $_className, $_value, $_attribute);
    }
    public static function input_date($_inputName = 'inputDate', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('date', $_inputName, $_className, $_value, $_attribute);
    }
    public static function input_time($_inputName = 'inputTime', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('time', $_inputName, $_className, $_value, $_attribute);
    }
    public static function input_number($_inputName = 'inputNumber', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('number', $_inputName, $_className, $_value, $_attribute);
    }
    public static function input_hidden($_inputName, $value)
    {
      return self::input('hidden', $_inputName, '', $value, '');
    }
    public static function input_password($_inputName = 'inputPassword', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('password', $_inputName, $_className, $_value, $_attribute);
    }
    public static function password_validate($_value, $_pattern)
    {
    }
    public static function input_email($_inputName = 'inputEmail', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('email', $_inputName, $_value, $_className, $_attribute);
    }
    public static function input_submit($_inputName = 'submit', $_value = 'submit', $_className = '', $_attribute = '')
    {
      return self::input('submit', $_inputName, $_className, $_value, $_attribute);
    }
    public static function input_radio($_inputName = 'inputRadio' , $_value, $_className = '', $_checked = '', $_attribute = '')
    {
      /*
      the pattern of $_value like this :
      $_value = array(
        'textName' = 'value'
      )
      */
      $_html = '';
      if (is_array($_value)) {
          foreach ($_value as $key => $value) {
          $_html .= '<label><input type=\'radio\' '
          .(($_className != '') ? 'class=\''.$_className.'\' ' : '')
          .(($_attribute != '') ? $_attribute : '')
          .' value=\''.$value.'\' name=\''.$_inputName.'\' '
          .(($value == $_checked) ? 'checked' : '')
          .'>$key</label>';
          }
        }else if(is_string($_value)) {
          $_html = '<label>'.self::input('radio', $_inputName, $_className, $value, $_attribute).'</label>';
        }
      return $_html;
    }
    public static function input_select($_inputName = 'inputSelect', $_className = '', $_attribute = '')
    {
      echo '<select '.(self::echo_null('name',$_inputName)).' '
      .(self::echo_null('class',$_className)).' '.$_attribute.'>';
    }
    public static function select_option($_inputName = 'inputSelect',$_data_arr = [], $_select = '', $_className = '', $_attribute = '')
    {
      self::input_select($_inputName, $_className, $_attribute);
      foreach ($_data_arr as $key => $value) {
        echo '<option value=\''.$value.'\' '.($_select == $value ? ' selected' : '').'>'.
        (is_numeric($key) ? $value : $key)
        .'</option>';
      }
      echo '</select>';
    }

    public static function input_option_db($_data_arr, $_key_value, $_key_text, $_select = '', $_className = '')
    {
      echo '<select '.self::echo_null('class', $_className).'>';
      foreach ($_data_arr as $value) {
        echo '<option value=\''.$value[$_key_value].'\' '.($_select == $value[$_key_value] ? ' selected' : '').'>'.
        $value[$_key_text]
        .'</option>';
      }
      echo '</select>';
    }
    public static function input_reset($_inputName = '', $_value = '', $_className = '', $_attribute = '')
    {
      return self::input('reset', $_inputName, $_className, $_value, $_attribute);
    }
    public static function input_textarea($_inputName = '', $_value = '', $_row = '2' , $_className = '', $_attribute = '')
    {
      return '<textarea rows=\''.$_row.'\' '.self::echo_null('name',$_inputName).' '
      .self::echo_null('',$_attribute).' '
      .self::echo_null('class',$_className) .'>'.$_value.'</textarea>';
    }
    public static function echo_null($_attr = '', $_value)
    {
      return ($_value == '' ? '' :  ($_attr == '' ? $_value : ' '.$_attr.'=\''.$_value.'\''));
    }

    public static function tag($tag,$content = '')
    {
        echo '<'.$tag.'>'.$content.'</'.$tag.'>';
    }

    static function convert_array($data_list, $_k, $_v)
    {
      $list = array();
      foreach ($data_list as $key => $value) {
        $list[$value[$_k]] = $value[$_v];
      }
      return $list;
    }
}
