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
  printPageHeader();
 	if ($input['numMemoryItems'] != '') {
 	  saveDataAndConfirm($input);
	} else if ($input['MemoryItemIDs'] != '') {
	  printEditPage($input);
	}
	printPageFooter();
}

function printPageHeader($bodyOnLoad = '') {
 	printSiteHeader('test', $bodyOnLoad);
	echo "<div style=\"height:600px\">\n"; 		
}

function printPageFooter() {
	echo "</div>\n";  
	printSiteFooter();  
}

/******************************** Page Print Functions *****************************************/

$tmpMemoryItemIDs;

function rowComparisonFunction($row1, $row2) {
  global $tmpMemoryItemIDs;
  
  $memoryItemID1 = $row1['MemoryItemID'];
  $memoryItemID2 = $row2['MemoryItemID'];  
  
  $index1 = array_search($memoryItemID1, $tmpMemoryItemIDs);
  $index2 = array_search($memoryItemID2, $tmpMemoryItemIDs);
  
  return $index1 - $index2;
}

function printEditPage($input) {
  global $tmpMemoryItemIDs;
  
	$memoryItemIDs = explode(";", $input['MemoryItemIDs']);  
	$tmpMemoryItemIDs = $memoryItemIDs;
  $sql = 'SELECT MemoryItemID, MemorySetID, CueText, DataText FROM memoryitem WHERE MemoryItemID IN (' . dbVals($memoryItemIDs) . ')';
  $memoryItemRows = getSQLRows($sql);
	
	usort($memoryItemRows, 'rowComparisonFunction');
	
	?>
	<h1>Edit Memory Items</h1>
	<form name="editForm">
	
	<table>
	<tr><td>Data Text</td><td>Cue Text</td>
	<?php
	$i = 0;
	foreach ($memoryItemRows as $memoryItemRow) {
	  if ($memoryItemRow != false) {
	    	$lastMemorySetID = $memoryItemRow['MemorySetID'];
		?>
		<tr>
		<td>		
		<input type="text" name="DataText_<?=$i?>" maxlength="255" style="width:300px;" autocomplete="off" 
		  value="<?=$memoryItemRow['DataText']?>"
			onkeydown="specialCharBoxKeyDown(event, document.editForm.DataText);"
			onkeypress="specialCharBoxKeyPress(event, document.editForm.DataText);"	
			onkeyup="specialCharBoxKeyUp(event, document.editForm.DataText);">
		</td>		
		<td>
		<input type="hidden" name="MemoryItemID_<?=$i?>" value="<?=$memoryItemRow['MemoryItemID']?>">	
		<input type="hidden" name="MemorySetID_<?=$i?>" value="<?=$memoryItemRow['MemorySetID']?>">	
		<input type="text" name="CueText_<?=$i?>" maxlength="255" style="width:300px;" autocomplete="off" 
			value="<?=$memoryItemRow['CueText']?>"
			onkeydown="specialCharBoxKeyDown(event, document.editForm.CueText);"
			onkeypress="specialCharBoxKeyPress(event, document.editForm.CueText);"	
			onkeyup="specialCharBoxKeyUp(event, document.editForm.CueText);">
		</td>
				
		<?php
		$i++;
		}
	}
	?>
	</table>

	<input type="hidden" name="numMemoryItems" value="<?=$i?>">	
	<input type="hidden" name="MemorySetID" value="<?=$lastMemorySetID?>">
	<br>
	<input type="submit" value="Update">
	
	</form>	
	<script language="JavaScript">document.editForm.CueText_0.focus();</script>
	<?php
}

function saveDataAndConfirm($input) {
	$numMemoryItems = $input['numMemoryItems'];

	$memoryItemIDs = array();
	
	for ($i = 0; $i < $numMemoryItems; $i++) {	  
	  $memoryItemID = $input['MemoryItemID_' . $i];
	  $cueText = $input['CueText_' . $i];
	  $dataText = $input['DataText_' . $i];

		if ($cueText == '' && $dataText == '')  {
			$sql = 'DELETE FROM memoryhistory WHERE MemoryItemID = ' . dbVal($memoryItemID);
			execSQL($sql);
			$sql = 'DELETE FROM memoryitem WHERE MemoryItemID = ' . dbVal($memoryItemID);
			execSQL($sql);
 		} else {	  
		  $sql = 'UPDATE MemoryItem SET DataText = ' . dbVal($dataText) . ', CueText = ' . dbVal($cueText) . 
			 	' WHERE MemoryItemID = ' . dbVal($memoryItemID);
			 	execSQL($sql);
		}
	  
	  array_push($memoryItemIDs, $memoryItemID);
  }
  
  echo '<i>Data updated successfully</i> <a href="test.php?MemorySetID=' . $input['MemorySetID'] . '">Go back to testing.</a>';

	$input['MemoryItemIDs'] = implode(';', $memoryItemIDs);
	printEditPage($input);  
}

?>
