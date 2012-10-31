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
	  printViewPage($input);
	} else {
	  printSelectMemorySetPage($input);
	}
}

function printPageHeader($bodyOnLoad = '') {
 	printSiteHeader('view', $bodyOnLoad);
}

function printPageFooter() {
	printSiteFooter();  
}

function getUserID() {
  return getSQLScalar('SELECT UserID FROM user WHERE UserName = ' . dbVal(getUserName()));
}

function getUserName() {
  return 'dave';
}

/******************************** Page Print Functions *****************************************/

function printStatistics($input) {
  $memorySetID = $input['MemorySetID'];
  $userID = getUserID();
  
  $sql = 'SELECT Category, COUNT(*) AS CategoryCount FROM memoryitempriority ' . 
		'WHERE MemorySetID = ' . dbVal($memorySetID) . ' AND (UserID = ' . dbVal($userID) . ' OR UserID IS NULL) ' .  
		' GROUP BY Category ORDER BY 2 DESC';
	$rows = getSQLRows($sql);

	$untested = 0;
	$unlearned = 0;	
	$learned = 0;		
	$needsPractice = 0;
	foreach ($rows as $row) {
	  if ($row['Category'] == 'Untested') {
	    $untested = $row['CategoryCount'];
	  } elseif ($row['Category'] == 'Unlearned') {
	    $unlearned = $row['CategoryCount'];
	  } elseif ($row['Category'] == 'Learned') {
	    $learned = $row['CategoryCount'];
	  } elseif ($row['Category'] == 'NeedsPractice') {
	    $needsPractice = $row['CategoryCount'];
	  }
	}
	echo 'Total Words: ' . ($untested + $unlearned + $learned + $needsPractice) . '<br>';
	echo 'Learned: ' . $learned . '<br>';
	echo 'Unlearned: ' . ($unlearned + $needsPractice) . '<br>';
	echo '<br>';

}

function printSelectMemorySetPage($input) {
  printPageHeader();
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
	printPageFooter();
}

function printViewPage($input) {
	printPageHeader();  
	
	printStatistics($input);
  
  $sql = 
		'SELECT memoryitem.MemoryItemID, memoryitem.CueText, memoryitem.DataText, Category, CorrectnessRatio ' .
		'FROM memoryitem ' . 
		'INNER JOIN memoryitempriority ' . 
		'ON memoryitem.MemoryItemID = memoryitempriority.MemoryItemID ' .
		'WHERE memoryitem.MemorySetID = ' . dbVal($input['MemorySetID']) . ' ' . 
		'AND (UserID IS NULL OR UserID = ' . dbVal(getUserID()) . ') ' . 
		'ORDER BY DataText, CueText';
		
	$rows = getSQLRows($sql);
	
	echo '<table>';
  echo "<tr><th>Data Text</th><th>Cue Text</th><th>Category</th><th>Percent Correct</th></tr>\n";	
	foreach ($rows as $row) {
	  echo "<tr>";
	  echo '<td><a href="edit.php?MemoryItemIDs=' . $row['MemoryItemID'] . '">' . $row['DataText'] . '</a></td>';
	  echo '<td><a href="edit.php?MemoryItemIDs=' . $row['MemoryItemID'] . '">' . $row['CueText'] . '</a></td>';
	  echo '<td>' . $row['Category'] . '</td>';	  
	  echo '<td align="right">' . round($row['CorrectnessRatio'] * 100.00, 0) . '%</td>';	  	  
		echo "</tr>\n";
	} 
	echo '</table>';
	
 	printPageFooter();  
}

?>
