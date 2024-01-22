<?php

use Ds\Foundations\Common\File;
use Ds\Foundations\Config\Env;

use function Ds\Base\App\Config\env;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exceptions</title>
    <style>
        <?php 
            echo (new File(__DIR__.'/src/prism.css'))->getContent();
            ?>
    </style>
    <style>
        * {
            font-family: roboto, arial, sans-serif;
            line-height: 1.5;
            font-size: 14px;
        }
        .BoxDsException__ {
            border-radius: 12px;
            box-shadow: 1px 1px 5px black;
            overflow: hidden;
            margin: 10px;
            font-family: monaco, times new roman;
            font-weight: 300;
        }

        .ContentDsException__ {
            margin: 2px;
            padding: 5px;
        }
        .HeaderDsException__ {
            position: relative;
            border-radius: 4px 4px 0px 0px;
            display: block;
            top: 0;
            padding: 10px;
            background: #182435;
            border-bottom: 4px solid #ff1100;
            font-size: 1.2em;
            text-align: center;
            color: white;
            font-weight: bold;
        }

        .CodeDsException {
            display: block;
            background-color: #000066;
            padding: 10px;
            color: white;
        }
        .boxErrorTree {
            box-shadow: 0 2px 5px grey;
            transition: 200ms ease-in-out;
            border-radius: 4px;
            display: inline-flex;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
        }
        .errorMessage{
            font-size: medium;
            margin: 0;
            padding: 8px;
        }

        .boxErrorTree:hover {
            background-color: whitesmoke;
        }

        .orange {
            color: orange;
        }

        .ds_line_break_error {
            background-color: #ee990045;
            height: 20px;
            width: 10000%;
            display: block;
            position: absolute;
            left: -12px;
            z-index: 10;
        }

        .errcode-lines, .errcode-lines-main {
            background: #182435;
            border-radius: 8px;
            border: 1px solid #bfbfbf;
            padding: 12px;
        }

        .ds_line_break_no {
            float: left;
            padding: 0 10px;
            color: #9a9a9a;
            text-align: right;
            background: #1d2d43;
        }
        .errcode-wrapper * {
            font-family: consolas, monospace, arial;
        }
        .errcode-wrapper {
            margin: 10px 0;
            width: 100%;
        }
        .hide {
            display: none;
        }
        .errcode-files ul li {list-style: none;padding: 8px 12px;box-sizing: border-box;cursor: pointer;font-weight: bold;font-size: small;border-collapse: collapse;border: 1px solid #00000026;}

        .errcode-files ul {
            padding: 0 10px;
        }

        .errcode-files ul li:hover {
            background: #dbdbdb;
        }
        li.file-selected {
            background: #cbcbcb;
        }
        pre[class*=language-]{
            padding: 0 0 0 12px;
            margin: 0;
        }
        code[class*=language-], pre[class*=language-] {
            text-shadow: none;
            color: cyan;
            z-index: 100;
            position: absolute;
            left: 43px;
            top: 0;
            width: 93%;
            height: 100%;
        }
        .token.important, .token.regex, .token.variable,.token.punctuation{
            color: #e7e7e7;
        }
        .language-css .token.string, .style .token.string, .token.entity, .token.operator, .token.url{
            background-color: transparent;
            color: #ff00d4;
        }
        .token.atrule, .token.attr-value, .token.keyword{
            color: #ff00d4;
        }
        .token.class-name, .token.function{
            color: #00ff00;
        }
        .token.boolean, .token.constant, .token.deleted, .token.number, .token.property, .token.symbol, .token.tag {
            color: orange;
        }
        .token.attr-name, .token.builtin, .token.char, .token.inserted, .token.selector, .token.string {
            color: #ffeb3b;
        }
    </style>
        <script>
            function showCode(e) {
                let debugLine = e.dataset['line'];
                let codeFiles = document.querySelectorAll('.errcode-files ul li');
                let codeLines = document.querySelectorAll('.errcode-lines');
                for (let i = 0; i < codeLines.length; i++) {
                    const element = codeLines[i];
                    const elementFile = codeFiles[i];
                    const elementCodeLine = element.dataset['line'];
                    if(elementCodeLine == debugLine){
                        element.classList.remove('hide');
                        elementFile.classList.add('file-selected');
                    }else{
                        element.classList.add('hide');
                        elementFile.classList.remove('file-selected');
                    }
                }
            }
        </script>
        <script>
            <?php 
            echo (new File(__DIR__.'/src/prism.js'))->getContent();
            ?>
        </script>
</head>

<body>
    <div class="BoxDsException__">
        <div class="HeaderDsException__">Error : <i> <?php echo $filename ?> </i></div>
        <div class="ContentDsException__">
            <div class="errcode-wrapper">
                <div class="errcode-lines-main">
                    <?php echo (empty($filename)) ? "" : $this->display_line_error(file($filename), $this->exception->getLine()); ?>
                </div>
            </div>
            <p class="errorMessage">
            <b>Message :</b> <?php echo $this->exception->getMessage(); ?>
            </p>
            <div>
                <?php echo $additionalMessage ?>
            </div>
        </div>
    </div>
    <?php if (Env::get('STATUS') != 'production') ?>
        <div class="boxErrorTree">
            <div class="errcode-files">
                <ul>
                <?php $i = 0 ?>
                <?php 
                foreach ($arrTrace as $trace) {
                    if(!isset($trace['file'])) continue;
                    ?>
                    <li onclick="showCode(this)" data-line="<?php echo $i ?>" class="<?php echo $i == 0 ? 'file-selected' : '' ?>">
                        <?php 
                        if(isset($trace['file'])){
                            echo str_replace(ROOT, '...', $trace['file']).'('.$trace['line'].')';
                        }else{
                            var_dump($trace);
                        }
                        ?>
                    </li>
                    <?php $i++ ?>
                <?php } ?>
                </ul>
            </div>
            <div class="errcode-wrapper">
            <?php $i = 0 ?>
            <?php foreach ($arrTrace as $trace) {
                    if(!isset($trace['file'])) continue; ?>
                <div class="errcode-lines <?php echo $i == 0 ? '' : 'hide' ?>" data-line="<?php echo $i ?>">
                    <?php
                        echo $this->display_line_error(file($trace['file']), $trace['line']);
                        $i++;
                    ?>
                </div>
            <?php } ?>
            </div>
        </div>
</body>

</html>