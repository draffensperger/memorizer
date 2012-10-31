<?php 
/******************************** Main Code Section *****************************************/

include 'functions.php';

define("FORWARD", 1);
define("BACKWARD", 2);

while (list($name, $value) = each($HTTP_POST_VARS)) { $input[$name] = stripslashes($value); }
while (list($name, $value) = each($HTTP_GET_VARS)) { $input[$name] = stripslashes($value); }
openDBConn();
printPage($input);
closeDBConn();

function printPage($input) {
	if ($input['MemorySetID'] == '') {
		printSelectMemorySetPage($input);
	} else {
	  printPageHeader('document.inputform.datatext.focus();');  

  	if ($input['datatext'] != '') {
  		addItemsAndPrintConfirmation($input);
		}
		
		printAddMemoryItemsPage($input);
	 	printPageFooter();  
	}
}

function printPageHeader($bodyOnLoad = '') {
 	printSiteHeader('add', $bodyOnLoad);
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

function printAddMemoryItemsPage($input) {
  echo "Enter the details for an item to insert.<br>";  
  echo "<form method=POST name=inputform>\n";
  
  echo "<table>\n";
	echo "<tr><td>Data Text</td><td>Cue Text</td></tr>\n";
	echo "<tr><td><input size=30 type=text name=\"datatext\" autocomplete=\"off\"></td>\n";
	echo "<td><input size=30 type=text name=\"cuetext\" autocomplete=\"off\"></td></tr>\n";
  echo "</table>\n";

  echo "<input type=hidden name=MemorySetID value=\"" . $input['MemorySetID'] . "\">";
  echo "<input type=submit value=\"Add Item\">";
  echo "</form>\n";
}

function addItemsAndPrintConfirmation($input) {		
	$dataText = $input['datatext'];
	$cueText = $input['cuetext']; 
	$memorySetID = $input['MemorySetID']; 
	
	if ($dataText != '' && $cueText != '') {
	  $sql = 'SELECT COUNT(*) FROM MemoryItem ' .
			'WHERE MemorySetID = ' . dbVal($memorySetID) . 
			' AND CueText = ' .  dbVal($cueText) . 
			' AND DataText = ' . dbVal($dataText);
		$countExisting = getSQLScalar($sql);
		
		if ($countExisting > 0) {
		  echo "There is already an item with the following attributes:<br>\n";
	 		echo "<table><tr><th>Data Text</th><th>Cue Text</th></tr>\n";
	 	 	echo "<tr><td>$dataText</td><td>$cueText</td>\n";	
			echo "</table><br>\n";	
		} else {
		  $sql = 'INSERT INTO MemoryItem (MemorySetID, DataText, CueText) ' . 
				'VALUES (' . dbVals(array($memorySetID, $dataText, $cueText)) . ')';	
	  	execSQL($sql);
	  	
			$memoryItemID = getLastInsertID($sql);
	  	
	  	echo "Successfully added the following items:<br>\n";
	 		echo "<table><tr><th>Data Text</th><th>Cue Text</th></tr>\n";
	 	 	echo "<tr><td><a href=\"edit.php?MemoryItemIDs=$memoryItemID\">$dataText</a></td>\n";
	 	 	echo "<td><a href=\"edit.php?MemoryItemIDs=$memoryItemID\">$cueText</a></td></tr>\n";	
			echo "</table><br>\n";	
	  }
  }
}

?>
