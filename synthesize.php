<?php

$file = "sounds/test.wav";

$fp=fopen($file,'r');
$content=fread($fp,filesize($file));
fclose($fp);

header('Content-type: audio/wav');
header('Content-disposition: inline');

echo $content;

?>
