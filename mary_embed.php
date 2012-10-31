<?php 
include 'functions.php';

while (list($name, $value) = each($HTTP_POST_VARS)) { $input[$name] = stripslashes($value); }
while (list($name, $value) = each($HTTP_GET_VARS)) { $input[$name] = stripslashes($value); }

$src = $input['src'];
echo '<html><body>';
echo '<embed src="' . $src . '" type="audio/x-wav" autostart=true loop=1 height=50 width=300>';		
echo '</body></html>';

?>
