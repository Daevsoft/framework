<?php
class dsException extends Exception
{
    private $exception;
    private $filename;
    private $filename_real;
    public function __construct($_exception, $filename = STRING_EMPTY,bool $show_line = TRUE)
    {
        $this->exception = $_exception;
        if($filename == STRING_EMPTY){
            if(isset($GLOBALS['FILENAMES'])){
                $this->filename = $GLOBALS['FILENAMES'];
            }else{
                $this->filename = $this->exception->getFile();
            }
            if(isset($GLOBALS['FILENAMES_REAL'])){
                $this->filename_real = $GLOBALS['FILENAMES_REAL'];
            }
        }else{
            $this->filename_real = $this->filename = $filename;
        }
        $this->show_exception($show_line);
    }
    public function show_exception(bool $_show_line)
    {
        // Get All Trace
        $arrTrace = $this->exception->getTrace();
        // Put Style Exception
        echo '<style>
        .BoxDsException__{
            border-radius:4px;
            box-shadow:1px 1px 5px black;
            font-weight:100;
            margin:10px;
            font-family:monaco,times new roman;
            font-weight:300;
        }
        .ContentDsException__{
            margin:2px;
            padding:5px;
        }
        .HeaderDsException__{
            position:relative;
            border-radius:4px 4px 0px 0px;
            display:block;
            top:0;
            padding:10px;
            background:red;
            color:white;
            font-size:1.2em;
            font-weight:bold;
        }
        .CodeDsException{
            display:block;
            background-color:#000066;
            padding:10px;
            color:white;
        }
        .ds_line_break_error{
            background-color: orange;
            color: black;
        }
        .boxErrorTree{
            border:0.5px solid black;
            padding:5px; 
            margin-top: 5px;
        }
        .boxErrorTree:hover{
            background-color:midnightblue;
            color:white;
        }
        .orange{
            color:orange;
        }
        </style>';
        echo '
        <div class="BoxDsException__">
        <div class="HeaderDsException__">Ouch..!</div>
        <div class="ContentDsException__">'
        .'Source File : <i>'. $this->filename_real
        .'</i><br />File From : <i>'. $this->filename
        .($_show_line ? 
        ('</i><br>Line : '.$this->exception->getLine()
        .'<pre class="CodeDsException">'
        .$this->display_line_error(file($this->filename), $this->exception->getLine())
        .'</pre>') : '<br />Error : ')
        .$this->exception->getMessage();
        echo '</div></div>';
if(config('status') != 'pub')
		foreach ($arrTrace as $trace) {
			echo '<div class="boxErrorTree">';
			foreach ($trace as $traceKey => $traceValue) {
                $errorDesc = is_array($traceValue) ? print_r($traceValue) : $traceValue;
				echo '<b class="orange">'.ucfirst($traceKey).'</b> : '.$errorDesc.'<br />';
			}
			echo '</div>';
        }
        die();
    }
    public function display_line_error($_arrFile, $_line)
    {
        $start_line = $_line-1;
        $end_line = $_line;
        $result = $line = STRING_EMPTY;
        if($start_line > 3) $start_line -= 3;
        if(count($_arrFile) > $end_line + 2) $end_line += 3;
        for ($i=$start_line; $i <= $end_line; $i++) { 
            $line = $i+1;
            $result .= '<div'.($line == $_line ? ' class="ds_line_break_error"' : STRING_EMPTY)
            .'><span style="display:inline-block;width:23px;text-align:right">'.$line.' </span>| '.htmlspecialchars($_arrFile[$i]).'</div>';
        }
        return $result;
    }
}

?>