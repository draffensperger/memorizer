<?php 
/******************************** Main Code Section *****************************************/

include 'functions.php';

define("FORWARD", 1);
define("BACKWARD", 2);

define("TEST_FORM_BODY_ONLOAD", 'document.memoryTestForm.guess.focus();');
define("RETRY_FORM_BODY_ONLOAD", 'document.retryForm.retestButton.focus();');

while (list($name, $value) = each($HTTP_POST_VARS)) { $input[$name] = stripslashes($value); }
while (list($name, $value) = each($HTTP_GET_VARS)) { $input[$name] = stripslashes($value); }
openDBConn();
printPage($input);
closeDBConn();

function printPage($input) {
	if ($input['MemorySetID'] != '') {
	  printStatsPage($input);
	} else {
	  printSelectMemorySetPage($input);
	}
}

function printPageHeader($bodyOnLoad = '') {
 	printSiteHeader('stats', $bodyOnLoad);
	echo "<div style=\"height:600px\">\n"; 	
}

function printPageFooter() {
	echo "</div>\n";  
	printSiteFooter();  
}

/******************************** Page Print Functions *****************************************/

function printSelectMemorySetPage($input) {
  printPageHeader();
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
	printPageFooter();
}

function printStatsPage($input) {
	printPageHeader();  
  
  echo 'Statistics and Memory Items<br>';
  
 	printPageFooter();  
}

?>
