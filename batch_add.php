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
  if ($input['newitems'] != '') {
  	addItemsAndPrintConfirmation($input);
	} else if ($input['MemorySetID'] != '') {
	  printAddMemoryItemsPage($input);
	} else {
	  printSelectMemorySetPage($input);
	}
}

function printPageHeader($bodyOnLoad = '') {
 	printSiteHeader('batch_add', $bodyOnLoad);
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
	printPageHeader();  
  echo "Enter in list of new items separated by colons or tabs (one item per line).<br>";
  echo "The format should be [Data Text] [: or tab] [Cue Text]. Cue Order is assumed to be 1 for all of them.<br>";  
  echo "<form method=POST>\n";
  echo "<textarea name=\"newitems\" rows=30 cols=80></textarea><br>";
  echo "<input type=hidden name=MemorySetID value=\"" . $input['MemorySetID'] . "\">";
  echo "<input type=submit value=\"Add Items\">";
  echo "</form>\n";
 	printPageFooter();  
}

function addItemsAndPrintConfirmation($input) {
	printPageHeader();    
  
  $newItemsStr = $input['newitems'];
	$memorySetID = $input['MemorySetID'];
	$newItemLines = explode("\n", $newItemsStr);
	
	echo "Here are the results of the batch add:<br>\n";
	echo "<table><tr><th>Data Text</th><th>Cue Text</th><th>Add Status</th></tr>\n";
	foreach ($newItemLines as $newItemLine) {	
	  list($dataText, $cueText) = split("[/\t\:]", $newItemLine);	 
		$dataText = trim($dataText);
		$cueText = trim($cueText); 

		if ($dataText == '' || $cueText == '') {
    	$addStatus = 'Blank';		  
    } else {		        
		  $sql = 'SELECT COUNT(*) FROM MemoryItem ' .
			'WHERE MemorySetID = ' . dbVal($memorySetID) . 
			' AND CueText = ' .  dbVal($cueText) . 
			' AND DataText = ' . dbVal($dataText);
			$countExisting = getSQLScalar($sql);
			
			if ($countExisting > 0) {
	    	$addStatus = 'Duplicate';
			} else {			
			  $sql = 'INSERT INTO MemoryItem (MemorySetID, DataText, CueText, CueOrder) ' . 
					'VALUES (' . dbVals(array($memorySetID, $dataText, $cueText)) . ', 1)';	
		  	execSQL($sql);
		  	$addStatus = 'Successful';
	  	}
	  }
		echo "<tr><td>$dataText</td><td>$cueText</td><td>$addStatus</td>\n";
	}
	
	echo "</table>\n";
	printPageFooter();  	
}

?>
