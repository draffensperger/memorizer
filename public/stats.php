<?php 
/******************************** Main Code Section *****************************************/

include 'functions.php';

define("FORWARD", 1);
define("BACKWARD", 2);

define("TEST_FORM_BODY_ONLOAD", 'document.memoryTestForm.guess.focus();');
define("RETRY_FORM_BODY_ONLOAD", 'document.retryForm.retestButton.focus();');

openDBConn();
printPage();
closeDBConn();

function printPage() {
	if (getInput('MemorySetID') != '') {
	  printStatsPage();
	} else {
	  printSelectMemorySetPage();
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

function printSelectMemorySetPage() {
  printPageHeader();
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
	printPageFooter();
}

function printStatsPage() {
	printPageHeader();  
  
  echo 'Statistics and Memory Items<br>';
  
 	printPageFooter();  
}

?>
