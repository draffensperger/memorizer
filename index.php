<?php 
include 'functions.php';

while (list($name, $value) = each($HTTP_POST_VARS)) { $input[$name] = stripslashes($value); }
while (list($name, $value) = each($HTTP_GET_VARS)) { $input[$name] = stripslashes($value); }

openDBConn();
printPage();
closeDBConn();

function printPage() {
  printSiteHeader('home');
	echo "<div style=\"height:600px\">\n"; 	  

	if ($input['MemorySetID'] == '') {
	  printSelectMemorySetPage($input);
	} else {
	  $memorySetName = getSQLScalar('SELECT MemorySetName FROM memoryset WHERE MemorySetID = ' . dbVal($input['MemorySetID']));
	  echo 'Select an action on the left for memory set ' . $memorySetName;
	}
	?>
	
	<?php

  echo "</div>\n";  	
	printSiteFooter();
}


function printSelectMemorySetPage($input) {
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="test.php?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
}

?>
