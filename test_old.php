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
	if ($input['correctAnswer'] != '') { 
	  printTestResultsPage($input);
	} else if ($input['MemoryItemID'] != '') {
	  printCurrentMemoryCuePage($input);
	} else if ($input['MemorySetID'] != '') {
	  printNextMemoryCuePage($input);
	} else {
	  printSelectMemorySetPage($input);
	}
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

function printSelectMemorySetPage($input) {
  printPageHeader();
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="test.php?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
	printPageFooter();
}

function printNextMemoryCuePage($input) {
  printPageHeader(TEST_FORM_BODY_ONLOAD);  
  $memorySetID = $input['MemorySetID'];

  $nextMemoryItemAndDir = getNextMemoryItemAndDirection(getUserID(), $memorySetID);
  $nextMemoryItem = $nextMemoryItemAndDir[0];

 	printContextItems($nextMemoryItem);
	printMemoryCue($nextMemoryItemAndDir);

  printPageFooter();
}

function printCurrentMemoryCuePage($input) {
  printPageHeader(TEST_FORM_BODY_ONLOAD);  
	$memoryItemID = $input['MemoryItemID'];
  $direction = $input['direction'];

  $nextMemoryItemAndDir = getMemoryItemAndDirectionFromID($memoryItemID, $direction);
  $nextMemoryItem = $nextMemoryItemAndDir[0];

 	printContextItems($nextMemoryItem);
	printMemoryCue($nextMemoryItemAndDir);


  printPageFooter();
}

function printTestResultsPage($input) {  	  
  $guess = $input['guess'];
  $correctAnswer = $input['correctAnswer'];
  $cue = $input['cue'];	  	  
  $direction = $input['direction'];
  $memoryItemID = $input['MemoryItemID'];
  $memorySetID = $input['MemorySetID'];
  $isCorrect = guessMatchesAnswer($guess, $correctAnswer);
  
  if ($isCorrect) {
	  printPageHeader(TEST_FORM_BODY_ONLOAD);
	} else {
	  printPageHeader(RETRY_FORM_BODY_ONLOAD);	  
	}
  
  updateHistory(getUserID(), $isCorrect, $direction, $memoryItemID);    
  
  if ($isCorrect) {
    $nextMemoryItemAndDir = getNextMemoryItemAndDirection(getUserID(), $memorySetID);
    $nextMemoryItem = $nextMemoryItemAndDir[0];
    
   	printContextItems($nextMemoryItem);
    
    if ($nextMemoryItem['MemoryItemID'] != $memoryItemID) {
    	printResultTable($isCorrect, $cue, $guess, $correctAnswer);
    } else {
      echo '<h1>Correct, but keep practicing.</h1>';
    }
    
	  printMemoryCue($nextMemoryItemAndDir);
	} else {
	  $memoryItem = getMemoryItemFromID($memoryItemID);
  	printContextItems($memoryItem);
	  printResultTable($isCorrect, $cue, $guess, $correctAnswer);
		printRetryForm($memoryItemID, $direction);
	}
	
  printPageFooter();	
}

/******************************** Print Utility Functions *****************************************/

function printContextItems($memoryItem) {
  $contextRows = getContextMemoryItems($memoryItem);
	if (is_array($contextRows) && count($contextRows) > 0 && $contextRows[0] != false) {
	  echo "<h1>Context</h1>\n";
 	  echo "<table>\n";
	  foreach($contextRows as $contextRow) {	    
    	echo '<tr><td>' . $contextRow['CueText'] . '</td><td>' . $contextRow['DataText'] . '</td></tr>' . "\n";
		}
 	  echo "</table>\n";	
	}
}

function printResultTable($isCorrect, $cue, $guess, $correctAnswer) {
  if ($isCorrect) {
	  $correctnessString = 'Correct';	    
  } else {
	  $correctnessString = 'Incorrect';	    
  }  	
	
  ?>
  <h1>Previous result: <?=$correctnessString?></h1>
	<table>
	<tr><td>Cue</td><td><?=htmlspecialchars($cue)?></h1></td></tr>
	<tr><td>Guess</td><td><?=htmlspecialchars($guess)?></h1></td></tr>
	<tr><td>Answer</td><td><?=htmlspecialchars($correctAnswer)?></h1></td></tr>
	</table>
	<?php
}

function printRetryForm($memoryItemID, $direction) {	  
  ?>
  <form name="retryForm" method="POST" action="test.php">
  <input type="submit" name="retestButton" value="Retest">
  <input type="hidden" name="MemoryItemID" value="<?=$memoryItemID?>">  
  <input type="hidden" name="direction" value="<?=$direction?>">    
	</form>
	<?php	
}

function printNextMemoryCue($userID, $memorySetID) {
	printMemoryCue(getNextMemoryItemAndDirection($userID, $memorySetID));
}

function printCurrentMemoryCue($memoryItemID, $direction) {
  printMemoryCue(getMemoryItemAndDirectionFromID($memoryItemID, $direction));
}

function printMemoryCue($memoryItemAndDir) {
  $memoryItem = $memoryItemAndDir[0];
  $direction = $memoryItemAndDir[1];

  $memorySetName = getSQLScalar('SELECT MemorySetName FROM memoryset WHERE MemorySetID = ' . dbVal($memoryItem['MemorySetID']));  
    
  if ($direction == FORWARD) {
    $correctAnswer = $memoryItem['DataText'];  
    $cue = $memoryItem['CueText'];
  } else if ($direction == BACKWARD) {
    $correctAnswer = $memoryItem['CueText'];    
    $cue = $memoryItem['DataText'];
  } else {
    die('Unexpected direction: ' . $direction);
  }
?>

<h1>Memory test for <?=$memorySetName?></h1>
<form name="memoryTestForm" method="POST" action="test.php" style="padding-top:0px">
<table>
<tr>
<td>
<?=$cue?>
</td>
</tr>
<tr>
<td>
<input type="text" name="guess" maxlength="255" style="width:780px;" autocomplete="off" 
	onkeydown="specialCharBoxKeyDown(event, document.memoryTestForm.guess);"
	onkeypress="specialCharBoxKeyPress(event, document.memoryTestForm.guess);"	
	onkeyup="specialCharBoxKeyUp(event, document.memoryTestForm.guess);">
<br><br>
<input type="submit" value="Test">

<input type="hidden" name="correctAnswer" value="<?=htmlspecialchars($correctAnswer)?>">
<input type="hidden" name="direction" value="<?=$direction?>">
<input type="hidden" name="cue" value="<?=$cue?>">
<input type="hidden" name="MemoryItemID" value="<?=$memoryItem['MemoryItemID']?>">
<input type="hidden" name="MemorySetID" value="<?=$memoryItem['MemorySetID']?>">

</td>
</tr>
</table>
</form>
<?
}

/******************************** Database Utility Functions *****************************************/

function guessMatchesAnswer($guess, $answer) {
  $findChars = array(',', '\'', '"', ':', ';', '.', '-', '!');  
  $replaceChars = array('', '', '', '', '', '', '', '');
  return trim(str_replace($findChars, $replaceChars, $guess)) == trim(str_replace($findChars, $replaceChars, $answer));
}

function getUserName() {
  return 'dave';
}

function getUserID() {
  return getSQLScalar('SELECT UserID FROM user WHERE UserName = ' . dbVal(getUserName()));
}

function getContextMemoryItems($memoryItem) {
  $contextLength = 5;
  $sql = 'SELECT * FROM memoryitem ' . 
	    	 'WHERE CueOrder < ' . dbVal($memoryItem['CueOrder']) .
	    	 ' AND CueOrder >= '  . dbVal($memoryItem['CueOrder'] - $contextLength);
	return getSQLRows($sql);
}

function updateHistory($userID, $isCorrect, $direction, $memoryItemID) {
  $whereCond = ' WHERE UserID = ' . dbVal($userID) . ' AND MemoryItemID = ' . dbVal($memoryItemID);
  $memoryHistoryRow = getSQLRow('SELECT * FROM memoryhistory' . $whereCond);

  if (isset($memoryHistoryRow)) {
   	$maxPracticeTimesNeeded = 
			getSQLScalar('SELECT NumPracticeTimes '. 
				'FROM memoryset INNER JOIN memoryitem ON memoryitem.MemorySetID = memoryset.MemorySetID ' . 
				'WHERE MemoryItemID = ' . dbVal($memoryItemID));
    $numPracticeTimesNeeded = $memoryHistoryRow['NumPracticeTimesNeeded'];

    if ($numPracticeTimesNeeded > 0) {
      if ($isCorrect) {
        $sql  = 'UPDATE memoryhistory SET NumPracticeTimesNeeded = NumPracticeTimesNeeded - 1' . $whereCond;
      } else {
        $sql  = 'UPDATE memoryhistory SET NumPracticeTimesNeeded = ' . dbVal($maxPracticeTimesNeeded) . $whereCond;
      }
    } else {    
	    $sql = 'UPDATE memoryhistory SET ';
			if ($direction == FORWARD) {
			  $sql .= ' NumForwardTested = NumForwardTested + 1';
			  if ($isCorrect) {
				  $sql .= ', NumForwardCorrect = NumForwardCorrect + 1';		    
			  }
			} else {
			  $sql .= ' NumBackwardTested = NumBackwardTested + 1';
			  if ($isCorrect) {
				  $sql .= ', NumBackwardCorrect = NumBackwardCorrect + 1';		    
			  }		  
			}		
			
			if ($isCorrect) {
			  $sql .= ', NumCorrectInARow = NumCorrectInARow + 1';
			} else {
			  $sql .= ', NumCorrectInARow = 0, NumPracticeTimesNeeded = ' . dbVal($maxPracticeTimesNeeded);
			}
			$sql .= ', LastTimeTested = NOW()';
			$sql .= $whereCond;
		}
  } else {  
	  if ($direction == FORWARD) {
	    $numBackwardTested = 0;
 	    $numBackwardCorrect = 0;
 	    
	    $numForwardTested = 1;
	    if ($isCorrect) {
			  $numForwardCorrect = 1;		        
	    } else {
	    	$numForwardCorrect = 0; 
	    }
	  } else {
	    $numForwardTested = 0;
 	    $numForwardCorrect = 0;
 	    
	    $numBackwardTested = 1;
	    if ($isCorrect) {
			  $numBackwardCorrect = 1;		        
	    } else {
	    	$numBackwardCorrect = 0; 
	    }
	  }
	  
	  if ($isCorrect) {
			$numCorrrectInARow = 1;
		} else {
 			$numCorrrectInARow = 0;
		}
    
	  $sql = 'INSERT memoryhistory (' . 
					 'UserID, MemoryItemID, ' .
					 	'NumForwardTested, NumForwardCorrect, NumBackwardTested, NumBackwardCorrect, NumCorrectInARow, LastTimeTested' . 
					 ') VALUES (' . 
					 dbVals(array($userID, $memoryItemID, 
					 	$numForwardTested, $numForwardCorrect, $numBackwardTested, $numBackwardCorrect, $numCorrrectInARow)) .  ', NOW()' . 
					 ')';	  
  }
  
	execSQL($sql);
}

function getMemoryItemFromID($memoryItemID) {
  return getSQLRow('SELECT * FROM memoryitem WHERE MemoryItemID = ' . $memoryItemID);
}

function getMemoryItemAndDirectionFromID($memoryItemID, $direction) {
  return array(getMemoryItemFromID($memoryItemID), $direction);
}

/* This is the core policy that decides which items to test a user on next. 
 * 
 * Basically, I want it to work like this:
 * - First test on items that they have attempted before but have made mistakes on.
 * - Next test the items sequentially, based on the order first and then based on the number of times tested and a randomness factor.
 * - Finally, test the items sequential items randomly every once in a while
 * One issue is that the history table may not have any entries in it.
 */
function getNextMemoryItemAndDirection($userID, $memorySetID) {  
  $memorySetRow = getSQLRow(
		'SELECT ForwardTestRatio, MinCorrectnessRatio, MinNumCorrectInARow, WorkingSetSize ' .
		'FROM memoryset WHERE MemorySetID = ' . dbVal($memorySetID));
 	if (!is_array($memorySetRow)) {
 	  die('No memory set for MemorySetID: ' . $memorySetID);
 	} 	
	$forwardTestRatio = $memorySetRow['ForwardTestRatio'];
 	$minCorrectnessRatio = $memorySetRow['MinCorrectnessRatio'];
 	$minNumCorrectInARow = $memorySetRow['MinNumCorrectInARow'];
 	$workingSetSize = $memorySetRow['WorkingSetSize'];
  
  $memoryItemFields = 'memoryitem.MemoryItemID, memoryitem.MemorySetID, memoryitem.CueOrder, ' . 
		'memoryitem.CueText, memoryitem.DataText, memoryitem.DataSoundFile';		 	
	
	$numCorrectInARow = 'IFNULL(memoryhistory.NumCorrectInARow, 1)';
	
	$correctnessRatio = 
	  '(CASE WHEN (IFNULL(memoryhistory.NumBackwardTested, 0.0) + IFNULL(memoryhistory.NumForwardTested, 0.0)) = 0.0 THEN 1.0 ELSE ' .
		'((IFNULL(memoryhistory.NumBackwardCorrect, 0.0) + IFNULL(memoryhistory.NumForwardCorrect, 0.0)) ' . 
		'/ (IFNULL(memoryhistory.NumBackwardTested, 0.0) + IFNULL(memoryhistory.NumForwardTested, 0.0))) END)';
  
  /* First look for something that is worse than the minimum correctness ratio. */
  $sql = "SELECT $memoryItemFields " .
 			   'FROM memoryhistory ' .
			   'INNER JOIN memoryitem ON memoryitem.MemoryItemID = memoryhistory.MemoryItemID ' . 
			   "WHERE ($correctnessRatio < $minCorrectnessRatio OR memoryhistory.NumCorrectInARow < " . dbVal($minNumCorrectInARow) . ')' .
				 ' AND memoryhistory.userID = ' . dbVal($userID) . 
				 ' AND memoryitem.MemorySetID = ' . dbVal($memorySetID) . ' ' .
 			   "ORDER BY memoryhistory.NumCorrectInARow, RAND() * $correctnessRatio " .
			   'LIMIT 1';
  $memoryItemRow = getSQLRow($sql);  
  
  if (!is_array($memoryItemRow)) {
	  $sql = 'SELECT memoryItem.CueOrder ' . 
				   'FROM memoryitem ' . 
				   'INNER JOIN memoryhistory ON memoryitem.MemoryItemID = memoryhistory.MemoryItemID ' . 
					 'WHERE memoryhistory.UserID = ' . dbVal($userID) . 
					 ' AND memoryitem.MemorySetID = ' . dbVal($memorySetID) . ' ' .
					 'ORDER BY memoryhistory.LastTimeTested DESC ' . 
					 'LIMIT 1';	
	  $lastTestedCueOrder = getSQLScalar($sql);
	  
	  if (is_null($lastTestedCueOrder)) {
	    $lastTestedCueOrder = -1;
	  }
	  
	  /* Select a randomized row in next cue order bracket. */
	  $sql = "SELECT $memoryItemFields " .
				  'FROM memoryitem ' .
				  'LEFT JOIN memoryhistory ON memoryitem.MemoryItemID = memoryhistory.MemoryItemID ' . 				  
				  'WHERE CueOrder > ' . dbVal($lastTestedCueOrder) .
	  			' AND memoryitem.MemorySetID = ' . dbVal($memorySetID) .
	  			" ORDER BY CueOrder, IFNULL(memoryhistory.NumCorrectInARow, 1), RAND() * $correctnessRatio " .
	  			'LIMIT 1';
	  $memoryItemRow = getSQLRow($sql);    
	  
	  if (!is_array($memoryItemRow)) {
	    /* There is no "next" item in the cue order, then return a randomized item in the first cue order bracket. */
		  $sql = "SELECT $memoryItemFields " .
	 				  'FROM memoryitem ' .		
  				  'LEFT JOIN memoryhistory ON memoryitem.MemoryItemID = memoryhistory.MemoryItemID ' . 				  
  				  'WHERE memoryitem.MemorySetID = ' . dbVal($memorySetID) .
		  			" ORDER BY CueOrder, IFNULL(memoryhistory.NumCorrectInARow, 1), RAND() * $correctnessRatio " .
		  			'LIMIT 1';
			$memoryItemRow = getSQLRow($sql);
			if (!is_array($memoryItemRow)) {
			  die('No memory set items for MemorySetID: ' . $memorySetID);
			}
	  }
  }
	
	/* Randomly select the direction based on the ForwardTestRatio of the memoryset. */
	$randFloat = (float)rand() / (float)getrandmax ();
	if ($randFloat <= $forwardTestRatio) {
	  $direction = FORWARD;
	} else {
	  $direction = BACKWARD;
	}
    
	return array($memoryItemRow, $direction);
}

?>
