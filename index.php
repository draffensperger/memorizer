<?php 
include 'functions.php';

openDBConn();
printPage();
closeDBConn();

function printPage() {
  printSiteHeader('home');
	echo "<div style=\"height:600px\">\n"; 	  

	if (getInput('MemorySetID') == '') {
	  printSelectMemorySetPage();
	} else {
	  $memorySetName = getSQLScalar('SELECT MemorySetName FROM memoryset WHERE MemorySetID = ' . dbVal(getInput('MemorySetID')));
	  echo 'Select an action on the left for memory set ' . $memorySetName;
	}
	?>
	
	<?php

  echo "</div>\n";  	
	printSiteFooter();
}


function printSelectMemorySetPage() {
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="test.php?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
}

?>
