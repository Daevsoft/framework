<?php

use Ds\Foundations\Config\Env;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exceptions</title>
    <style>
    </style>
    <style>
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
                if (elementCodeLine == debugLine) {
                    element.classList.remove('hide');
                    elementFile.classList.add('file-selected');
                } else {
                    element.classList.add('hide');
                    elementFile.classList.remove('file-selected');
                }
            }
        }
    </script>
    <script>
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
                    if (!isset($trace['file'])) continue;
                ?>
                    <li onclick="showCode(this)" data-line="<?php echo $i ?>" class="<?php echo $i == 0 ? 'file-selected' : '' ?>">
                        <?php
                        if (isset($trace['file'])) {
                            $fname = str_replace(ROOT, '...', $trace['file']) . '(' . $trace['line'] . ')';
                            echo $fname;
                        } else {
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
                if (!isset($trace['file'])) continue; ?>
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