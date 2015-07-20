<?php 
/******************************** Main Code Section *****************************************/

include 'functions.php';
include 'mary_embed.php';

define("FORWARD", 1);
define("BACKWARD", 2);

define("TEST_FORM_BODY_ONLOAD", '');
define("RETRY_FORM_BODY_ONLOAD", '');

openDBConn();
printPage();
closeDBConn();

function printPage() { 	
	if (getInput('correctAnswer') != '') { 
	  printTestResultsPage();
	} else if (getInput('MemoryItemIDs') != '') {
	  //printCurrentMemoryCuePage();
	  $memoryItemIDs = explode(";", getInput('MemoryItemIDs'));
	  setInput('MemorySetID', getSQLScalar('SELECT MemorySetID FROM memoryitem WHERE MemoryItemID = ' . $memoryItemIDs[0]));
	  printNextMemoryCuePage();
	} else if (getInput('MemorySetID') != '') {
	  printNextMemoryCuePage();
	} else {
	  printSelectMemorySetPage();
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

function printSelectMemorySetPage() {
  printPageHeader();
  echo "<h1>Select Memory Set</h1>\n";
  $memory_sets = getSQLRows('SELECT MemorySetID, MemorySetName FROM memoryset');
 
	foreach ($memory_sets as $memory_set) {
	  echo '<a href="quiz.php?MemorySetID=' . $memory_set['MemorySetID'] . '">' . $memory_set['MemorySetName'] . '</a><br>';
	}
	printPageFooter();
}

function printStatistics() {
  $memorySetID = getInput('MemorySetID');
  $userID = getUserID();
  
  $sql = 'SELECT Category, COUNT(*) AS CategoryCount FROM memoryitempriority ' . 
		'WHERE MemorySetID = ' . dbVal($memorySetID) . ' AND (UserID = ' . dbVal($userID) . ' OR UserID IS NULL) ' .  
		' GROUP BY Category ORDER BY 2 DESC';
	$rows = getSQLRows($sql);
	echo '<small>';
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
	echo 'Stats: ' . $learned . ' + ' . ($unlearned + $needsPractice) . ' / ' . ($untested + $unlearned + $learned + $needsPractice);
	
	$quizMode = getInput('quizMode');
	if ($quizMode == 1) {
	  $quizStartTime = getSQLScalar('SELECT QuizStartTime FROM memoryset WHERE MemorySetID = ' . dbVal($memorySetID));
	  $numQuizzed = getSQLScalar(
			'SELECT COUNT(*) FROM memoryhistory ' . 
			'INNER JOIN memoryitem ON memoryhistory.MemoryItemID = memoryitem.MemoryItemID ' . 
			'WHERE memoryitem.MemorySetID = ' . dbVal($memorySetID) . ' AND (UserID = ' . dbVal($userID) . ' OR UserID IS NULL) ' .  
			'AND memoryhistory.LastTimeTested >= ' . dbVal($quizStartTime));
    echo ' / ' . $numQuizzed;
		echo '&nbsp; <a href="?quizMode=0&MemorySetID=' . $memorySetID . '"><small>Regular Mode</small></a>';	  
		echo '&nbsp;&nbsp; <a href="?quizMode=1&restartQuiz=1&MemorySetID=' . $memorySetID . '"><small>Restart quiz</small></a>';	  		
	} else {
		echo '&nbsp; <a href="?quizMode=1&MemorySetID=' . $memorySetID . '"><small>Quiz Mode</small></a>';
	}
	echo '<br>';
	echo '</small>';
}

function printNextMemoryCuePage() {
  printPageHeader(TEST_FORM_BODY_ONLOAD);  

	$memorySetID = getInput('MemorySetID');
  $quizMode = getInput('quizMode');

  if (getInput('restartQuiz') == 1) {
    $sql = 'UPDATE memoryset SET QuizStartTime = NOW() WHERE MemorySetID = ' . dbVal($memorySetID);
    execSQL($sql);
  }  
  
  printStatistics();    

  $nextMemoryItemAndDir = getNextMemoryItemAndDirection(getUserID(), $memorySetID, $quizMode);
  $nextMemoryItem = $nextMemoryItemAndDir[0];

	printMemoryCue($nextMemoryItemAndDir, $quizMode);

  printPageFooter();
}

function printCurrentMemoryCuePage() {
  printPageHeader(TEST_FORM_BODY_ONLOAD);  
	$memoryItemIDs = explode(";", getInput('MemoryItemIDs'));
	$memoryItemID = $memoryItemIDs[0];
	$quizMode = getInput('quizMode');	

  //$direction = getInput('direction');
  echo 'Not supposed to be here!!!';

  $nextMemoryItemAndDir = getMemoryItemAndDirectionFromID($memoryItemID, $direction);
  $nextMemoryItem = $nextMemoryItemAndDir[0];

	printMemoryCue($nextMemoryItemAndDir, $quizMode);


  printPageFooter();
}

function printTestResultsPage() {  	    
  $guesses = explode(";", getInput('guess'));
  $guesses = array_map('trim', $guesses);
  sort($guesses);
  $guess = implode("; ", $guesses);    
  
  $correctAnswer = getInput('correctAnswer');
  $cue = getInput('cue');	  	  
  $direction = getInput('direction');      
  $memoryItemIDs = explode(";", getInput('MemoryItemIDs'));
  $memorySetID = getInput('MemorySetID');
  $isCorrect = guessMatchesAnswer($guess, $correctAnswer);    
  
  $quizMode = getInput('quizMode');
  
  if ($isCorrect) {
	  printPageHeader(TEST_FORM_BODY_ONLOAD);
	} else {
	  printPageHeader(RETRY_FORM_BODY_ONLOAD);	  
	}
	
	foreach ($memoryItemIDs as $memoryItemID) {
		updateHistory(getUserID(), $isCorrect, $direction, $memoryItemID);    
	}
	
	printStatistics();
  
  if ($isCorrect) {
    $nextMemoryItemAndDir = getNextMemoryItemAndDirection(getUserID(), $memorySetID, $quizMode);
    $nextMemoryItem = $nextMemoryItemAndDir[0];  
    
    $curMemoryItem = getSQLRow('SELECT * FROM memoryitem WHERE MemoryItemID = ' . $memoryItemID);
    $curCue = getCueForMemoryItem($curMemoryItem);
		$curAnswerRows = getAnswerRowsForMemoryItem($curMemoryItem, $cue);
		$curAnswerText = implode('; ', extractColumn($curAnswerRows, 0));
		    
    $nextCue = getCueForMemoryItem($nextMemoryItem);
		$nextAnswerRows = getAnswerRowsForMemoryItem($nextMemoryItem, $nextCue);
		$nextAnswerText = implode('; ', extractColumn($nextAnswerRows, 0));		
		
    if (($curCue == $nextCue) && ($curAnswerText == $nextAnswerText)) {
      echo '<h1>Correct, but keep practicing.</h1>';      
    } else {
     	printResultTable($isCorrect, $cue, $guess, $correctAnswer, $memoryItemIDs);
    	printSound($correctAnswer);
    }
    
	  printMemoryCue($nextMemoryItemAndDir, $quizMode);
	} else {
	  $memoryItem = getMemoryItemFromID($memoryItemIDs[0]);
	  printResultTable($isCorrect, $cue, $guess, $correctAnswer, $memoryItemIDs);
   	printSound($correctAnswer);	     	
   	printAlternateAnswers($guess, $memoryItemIDs, $memorySetID, $correctAnswer);   	
		printRetryForm($memoryItemIDs, $direction, $quizMode);
	}	
	
  printPageFooter();	
}

$tmpGuesses;

function compareAlternateAnswerRows($row1, $row2) {
  global $tmpGuesses;
     
  return bestAlignmentToGuesses($row2['DataText'], $tmpGuesses) - 
		bestAlignmentToGuesses($row1['DataText'], $tmpGuesses);
}

function bestAlignmentToGuesses($text, $guesses) {
  $bestScore = -99999999;
  $text = removeGenderArticle($text);
  foreach ($guesses as $guess) {    
	  $guessSimplified = removeGenderArticle($guess);  	  
  	list($alignment, $score) = getStringAlignment($text, $guessSimplified);
  	//$score = similar_text($text, $guess);
	  
	  if ($score > $bestScore) {
	    $bestScore = $score;
	  }
	}  
	
	return $bestScore;
}

function getTopAlternateRows($rows, $maxRowsAtStart) {
  $topRows = array();
  $len = sizeof($rows); 
  for ($i = 0; $i < $len; $i++) {
    $j = sizeof($topRows);
    while ($j > 0 && compareAlternateAnswerRows($topRows[$j - 1], $rows[$i]) > 0) {
      $j--;
    }
    
    if ($j < sizeof($topRows) || sizeof($topRows) == 0) {
      $maxK = min(sizeof($topRows) + 1, $maxRowsAtStart);
      for ($k = $maxK - 1; $k > $j; $k--) {
        $topRows[$k] = $topRows[$k - 1];
      }
      $topRows[$j] = $rows[$i];    
    }
  }  
  
  return $topRows;
}

function removeGenderArticle($str) {
	return str_replace('das ', '', str_replace('der ', '', str_replace('die ', '', $str)));
}

function printAlternateAnswers($guessText, $memoryItemIDs, $memorySetID, $correctAnswerText) {
  global $tmpGuesses;  
  
  $guesses = explode(';', $guessText);
  $guesses = array_map('trim', $guesses);
  $tmpGuesses = $guesses;
  
  $answers = explode(';', $correctAnswerText);
	$answers = array_map('removeGenderArticle', $answers);  
  foreach ($guesses as $guess) {
    $guess = removeGenderArticle($guess);
    if (!(array_search($guess, $answers) === false)) {
      // If a guess is one of the answers but not all, then 
      // no need to print out alternate answers.
      return;
    }
  }
  
  $sql = 
		'SELECT MemoryItemID, DataText ' .
		'FROM memoryitem ' . 
		'WHERE MemoryItemID NOT IN (' . dbVals($memoryItemIDs) . ')';
	
	$sql .= ' AND MemorySetID = ' . dbVal($memorySetID);	
	$sql .= ' AND (';
	
	$numNonBlankGuesses = 0;
	foreach ($guesses as $guess) {
	  if ($guess != '') {
	 	  if ($numNonBlankGuesses > 0) {
		    $sql .= ' OR ';
		  }	  		   		   		  
		  
		  // Search without the German gender at the start of the word
			$guess = removeGenderArticle($guess);
 		  
			$sql .= 'REPLACE(REPLACE(REPLACE(DataText, \'der \', \'\'), \'die \', \'\'), \'das \', \'\')  LIKE ' . dbVal('%' . $guess . '%');
		  $sql .= ' OR ' . dbVal($guess) . ' LIKE ' .
				'CONCAT(\'%\', REPLACE(REPLACE(REPLACE(DataText, \'der \', \'\'), \'die \', \'\'), \'das \', \'\'), \'%\')';		  
 		  $sql .= ' OR DataText SOUNDS LIKE ' . dbVal($guess);
			 			  		  
 		  
  	  $numNonBlankGuesses++;
		}
	}
	
	$sql .= ')';
		
	if ($numNonBlankGuesses > 0) {
	  $minMatchingAlternateRows = array();
		$alternateRows = getSQLRows($sql);	
	
		$minScorePerCharacter = -0.8;
		
		for ($i = 0; $i < sizeof($alternateRows); $i++) {
		  if ($alternateRows[$i] != false) {
	 		  $dataText = $alternateRows[$i]['DataText'];
			  $score = bestAlignmentToGuesses($dataText, $guesses);

			  if ($score / strlen($dataText) > $minScorePerCharacter) {
			    array_push($minMatchingAlternateRows, $alternateRows[$i]);
			  }
		  }
		}
		$alternateRows = $minMatchingAlternateRows;
		
		if (sizeof($alternateRows) > 0) {	
		  $maxAlternateRows = 4;
		  $alternateRows = 	getTopAlternateRows($alternateRows, $maxAlternateRows);

			$alternateMemoryItemIDs = array();		  
		  $dataTexts = array();
		  foreach ($alternateRows as $row) {
		    $dataText = $row['DataText'];
		    if (array_search($dataText, $dataTexts) === false) {
			    array_push($dataTexts, $dataText);
			  }
			  array_push($alternateMemoryItemIDs, $row['MemoryItemID']);
		  }
		  
		  $cueTextsMap = getSQLMap(
				'SELECT DataText, GROUP_CONCAT(CueText ORDER BY CueText SEPARATOR \'; \') AS CueTexts ' . 
				'FROM MemoryItem WHERE DataText IN (' . dbVals($dataTexts) . ') ' .
				'AND MemorySetID = ' . dbVal($memorySetID) . ' GROUP BY DataText', 'DataText', 'CueTexts');			
		  
		  ?>
		  <br>
		  <span class="bigletters">Were you thinking of?</span>
		  <table>
		  <tr>
		  <td><span class="bigletters">Data Text</span></td>
			<td><span class="bigletters">&nbsp;&nbsp;Cue Text</span></td>	  
		  </tr>
		  <?php

			foreach($dataTexts as $dataText) {
			  if ($row != false) {
				  ?>
				  <tr>
					  <td><span class="bigletters"><?php echo(formatCue($dataText)); ?></span></td>				  
					  <td><span class="bigletters">&nbsp;&nbsp;<?php echo(formatCue($cueTextsMap[$dataText])); ?></span></td>
				  </tr>
				  <?php		  
				  array_push($alternateMemoryItemIDs, $row['MemoryItemID']);
			  }
			}	

			$alternateMemoryItemIDs = array_merge($memoryItemIDs, $alternateMemoryItemIDs);
			$memoryItemIDsText = implode(';', $alternateMemoryItemIDs);
		  ?>
		  </table>
		  <a href="edit.php?MemoryItemIDs=<?php echo($memoryItemIDsText); ?>">Edit these</a>
		  <?php		
		}
	}
}

/******************************** Print Utility Functions *****************************************/

function printResultTable($isCorrect, $cue, $guess, $correctAnswer, $memoryItemIDs) {
  if ($isCorrect) {
	  $correctnessString = 'Correct';	    
  } else {
	  $correctnessString = 'Incorrect';	    
  }  	

  list($guessFormatting, $answerFormatting) = formatGuessAndAnswer($guess, $correctAnswer);
	
  ?>
  <h1>Previous result: <?php echo($correctnessString); ?></h1>
	<table>
	<tr><td><span class="bigletters">Cue</span></td><td><span class="bigletters"><?php echo(formatCue($cue)); ?></span></td></tr>
	
	<tr><td><span class="bigletters">Guess</span></td><td><span class="bigletters"><?php echo($guessFormatting); ?></span></td></tr>
	<tr><td><span class="bigletters">Answer</span></td><td><span class="bigletters"><?php echo($answerFormatting); ?>
			
			&nbsp;&nbsp;<a href="edit.php?MemoryItemIDs=<?php echo(implode(';', $memoryItemIDs)); ?>">Edit</a>
			
			&nbsp;<a href="http://www.dict.cc/?s=<?php echo(urlencode($correctAnswer)); ?>">dict.cc</a>
			
			</span>
			</td></tr>
		
	</table>
	<?php
}

function printRetryForm($memoryItemIDs, $direction, $quizMode) {	  
  ?>
  <form name="memoryTestForm" method="POST" action="quiz.php">
  <input type="submit" name="guess" value="Retest">
	<script language="JavaScript">document.memoryTestForm.guess.focus();</script>
  <input type="hidden" name="MemoryItemIDs" value="<?php echo(implode(';', $memoryItemIDs)); ?>">  
  <input type="hidden" name="direction" value="<?php echo($direction); ?>">    
  <input type="hidden" name="quizMode" value="<?php echo($quizMode); ?>">      
	</form>
	<?php	
}

/*
function printNextMemoryCue($userID, $memorySetID, $quizMode) {
	printMemoryCue(getNextMemoryItemAndDirection($userID, $memorySetID), $quizMode);
}

function printCurrentMemoryCue($memoryItemID, $direction, ) {
  printMemoryCue(getMemoryItemAndDirectionFromID($memoryItemID, $direction), $quizMode);
}
*/

function getCueForMemoryItem($memoryItem) {
	$cue = getSQLScalar(
		'SELECT GROUP_CONCAT(CueText ORDER BY CueText SEPARATOR \'; \') ' .
		'FROM memoryitem WHERE MemorySetID = ' . dbVal($memoryItem['MemorySetID']) . ' AND DataText = ' . dbVal($memoryItem['DataText']));	
	return $cue;  
}

function getAnswerRowsForMemoryItem($memoryItem, $cue) {
  $memorySetID = $memoryItem['MemorySetID'];
	$sql = 	
	  'SELECT t1.DataText ' .
		'FROM (' . 
			'SELECT DataText, GROUP_CONCAT(CueText ORDER BY CueText SEPARATOR \'; \' ) AS CueTexts ' . 
		  'FROM memoryitem ' . 
		  'WHERE MemorySetID = ' . dbVal($memorySetID) . ' ' .
			'GROUP BY DataText' .
		') AS t1 ' . 
		'WHERE t1.CueTexts = ' . dbVal($cue) .
		' UNION ' . 
		'SELECT DataText ' . 
	  'FROM memoryitem ' . 
		'WHERE MemorySetID = ' . dbVal($memorySetID) . ' AND CueText = ' . dbVal($cue) . 
		' ORDER BY 1';	
	return getSQLRows($sql);  
}

function printMemoryCue($memoryItemAndDir, $quizMode) {       
  $memoryItem = $memoryItemAndDir[0];
  $direction = $memoryItemAndDir[1];
  $memorySetID = $memoryItem['MemorySetID'];

  $memorySetName = getSQLScalar('SELECT MemorySetName FROM memoryset WHERE MemorySetID = ' . dbVal($memorySetID));  

	// We only support forward now
  if ($direction != FORWARD) {
    die('Unexpected direction: ' . $direction);
  }

  $cue = getCueForMemoryItem($memoryItem);
	
  $answerRows = getAnswerRowsForMemoryItem($memoryItem, $cue);

  $answers = extractColumn($answerRows, 0);
  $correctAnswer = implode('; ', $answers);

  $sql = 
        'SELECT MemoryItemID FROM memoryitem	' .
        'WHERE MemorySetID = ' . dbVal($memorySetID) . ' AND DataText IN (' . dbVals($answers) . ')';
  $memoryItemIDRows = getSQLRows($sql);
  $memoryItemIDs = implode(';', extractColumn($memoryItemIDRows, 0));	
	
?>

<h1>Memory test for <?php echo($memorySetName); ?></h1>
<form name="memoryTestForm" method="POST" action="quiz.php" style="padding-top:0px">
<input type=hidden name=quizMode value="<?php echo($quizMode); ?>">
<table>
<tr>
<td>
<span class="bigletters"><?php echo $cue; ?></span>
</td>
</tr>
<tr>
<td>
<span class="bigletters">
<input type="text" name="guess" maxlength="255" class="bigletters" style="width:780px" autocomplete="off" 
	onkeydown="specialCharBoxKeyDown(event, document.memoryTestForm.guess);"
	onkeypress="specialCharBoxKeyPress(event, document.memoryTestForm.guess);"	
	onkeyup="specialCharBoxKeyUp(event, document.memoryTestForm.guess);"
        autofocus>
</span>	
<script language="JavaScript">document.memoryTestForm.guess.focus();</script>
<br><br>
<input type="submit" value="Test">

<input type="hidden" name="correctAnswer" value="<?php echo(htmlspecialchars($correctAnswer)); ?>">
<input type="hidden" name="direction" value="<?php echo($direction); ?>">
<input type="hidden" name="cue" value="<?php echo($cue); ?>">
<input type="hidden" name="MemoryItemIDs" value="<?php echo($memoryItemIDs); ?>">
<input type="hidden" name="MemorySetID" value="<?php echo($memoryItem['MemorySetID']); ?>">

</td>
</tr>
</table>
</form>
<?php
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

function updateHistory($userID, $isCorrect, $direction, $memoryItemID) {
  $whereCond = ' WHERE UserID = ' . dbVal($userID) . ' AND MemoryItemID = ' . dbVal($memoryItemID);
  $memoryHistoryRow = getSQLRow('SELECT * FROM memoryhistory' . $whereCond);

 	$maxPracticeTimesNeeded = 
		getSQLScalar('SELECT NumPracticeTimes '. 
			'FROM memoryset INNER JOIN memoryitem ON memoryitem.MemorySetID = memoryset.MemorySetID ' . 
			'WHERE MemoryItemID = ' . dbVal($memoryItemID));

  if (isset($memoryHistoryRow) && $memoryHistoryRow != false) {
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
			$numPracticeTimesNeeded = 0;
		} else {
 			$numCorrrectInARow = 0;
 			$numPracticeTimesNeeded = $maxPracticeTimesNeeded;
		}
    
	  $sql = 'INSERT memoryhistory (' . 
					 'UserID, MemoryItemID, ' .
					 	'NumForwardTested, NumForwardCorrect, NumBackwardTested, NumBackwardCorrect, NumCorrectInARow, LastTimeTested, ' . 
					 	'NumPracticeTimesNeeded' . 
					 ') VALUES (' . 
					 dbVals(array($userID, $memoryItemID, 
					 	$numForwardTested, $numForwardCorrect, $numBackwardTested, $numBackwardCorrect, $numCorrrectInARow)) .  ', NOW()' . 
					 	',' . dbVal($numPracticeTimesNeeded) . 
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
function getNextMemoryItemAndDirection($userID, $memorySetID, $quizMode) {  
  $memorySetRow = getSQLRow(
		'SELECT ForwardTestRatio, MinCorrectnessRatio, MinNumCorrectInARow, WorkingSetSize, NewVocabRatio ' .
		'FROM memoryset WHERE MemorySetID = ' . dbVal($memorySetID));  
 	if (!is_array($memorySetRow)) {
 	  die('No memory set for MemorySetID: ' . $memorySetID);
 	} 	
	$forwardTestRatio = $memorySetRow['ForwardTestRatio'];
 	$minCorrectnessRatio = $memorySetRow['MinCorrectnessRatio'];
 	$minNumCorrectInARow = $memorySetRow['MinNumCorrectInARow'];
 	$workingSetSize = $memorySetRow['WorkingSetSize'];
  $newVocabRatio = $memorySetRow['NewVocabRatio'];
	
	if ($quizMode == 1) {
	  // In quiz mode we select the item that was tested farther in the past.
 	  $quizStartTime = getSQLScalar('SELECT QuizStartTime FROM memoryset WHERE MemorySetID = ' . dbVal($memorySetID));
	  $sql = 'SELECT memoryitem.MemoryItemID, memoryitem.MemorySetID, ' . 
					 'memoryitem.CueText, memoryitem.DataText ' .
					 'FROM memoryitem LEFT JOIN memoryhistory ON memoryitem.MemoryItemID = memoryhistory.MemoryItemID ' .
					 'WHERE memoryitem.MemorySetID = ' . dbVal($memorySetID) . ' ' .
					 'AND (memoryhistory.UserID = ' . dbVal($userID) . ' OR memoryhistory.UserID IS NULL) ' . 
					 'AND memoryhistory.LastTimeTested < ' . 	dbVal($quizStartTime) . ' ' .
					 'ORDER BY RAND() ' .
					 'LIMIT 1';
 	  $memoryItemRow = getSQLRow($sql);            
	}

	if ($quizMode != 1 || $memoryItemRow == false) {
		$sql = 'SELECT Category, COUNT(*) AS CategoryCount FROM memoryitempriority ' . 
					 'WHERE (memoryitempriority.userID = ' . dbVal($userID) .  ' OR memoryitempriority.userID IS NULL) ' . 
					 ' AND memoryitempriority.MemorySetID = ' . dbVal($memorySetID) . ' ' .
					 'GROUP BY Category';
		$categoryRows = getSQLRows($sql);
		
		$numNeedsPractice = 0;
		$numUnlearned = 0;
		$numLearned = 0;
		$numUntested = 0;			
		
		foreach ($categoryRows as $categoryRow) {
		  if ($categoryRow['Category'] == 'NeedsPractice') {
		    $numNeedsPractice = $categoryRow['CategoryCount'];
		  } else if ($categoryRow['Category'] == 'Unlearned') {
		    $numUnlearned = $categoryRow['CategoryCount'];
		  } else if ($categoryRow['Category'] == 'Learned') {
		    $numLearned = $categoryRow['CategoryCount'];
		  } else if ($categoryRow['Category'] == 'Untested') {
		    $numUntested = $categoryRow['CategoryCount'];
		  }
		}
		
		if ($numUntested == 0) {
		  echo 'No ones untested..';
		}	
				
		if ($numNeedsPractice > 0) {
		  $selectCategory = 'NeedsPractice';
		} else {
		  $probTestUnlearned = $numUnlearned / $workingSetSize;
                  
                  
		  if ($numUntested > 0) {    
			  $probTestUntested = (1 - $probTestUnlearned) * $newVocabRatio;
			} else {
			  $probTestUnlearned = $probTestUnlearned + (1 - $probTestUnlearned) * $newVocabRatio;
			  $probTestUntested = 0;
			}
			
		  $probTestLearned = 1 - $probTestUntested - $probTestUnlearned;
		  
		  $rand = randFloat();
		  
		  //echo 'Unlearned: ' . $probTestUnlearned . ', untested: ' . $probTestUntested . 
			//', learned: ' . $probTestLearned . ', rand: ' . $rand . 
			//', num untested: ' . $numUntested . ' new vocab ratio: ' . $newVocabRatio;
		  
		  if ($rand < $probTestUnlearned) {
		    if ($numUnlearned > 0) {
	   	  	$selectCategory = 'Unlearned';
		    } else if ($numUntested > 0) {
			  	$selectCategory = 'Untested';
		    } else {
			  	$selectCategory = 'Learned';	      
		    }
		  } else if ($rand < ($probTestUnlearned + $probTestUntested)) {
		    if ($numUntested > 0) {
			    $selectCategory = 'Untested';
			  } else if ($numUnlearned > 0) {
				  $selectCategory = 'Unlearned';		    
				} else {
					$selectCategory = 'Learned';			  
				}
		  } else {
		    $selectCategory = 'Learned';	    
		  }
		}
	                          
	  /* First look for something that is worse than the minimum correctness ratio. */
          // Order it by whether it fits the category, but initially, they are all untested.
	  $sql = 'SELECT memoryitem.MemoryItemID, memoryitem.MemorySetID, ' . 
					 'memoryitem.CueText, memoryitem.DataText ' .
	 			   'FROM memoryitempriority ' . 
	 			   'INNER JOIN memoryitem ON memoryitem.MemoryItemID = memoryitempriority.MemoryItemID ' . 
				   'WHERE (memoryitempriority.UserID IS NULL OR memoryitempriority.UserID = ' . dbVal($userID) . ')' .  
					 ' AND memoryitempriority.MemorySetID = ' . dbVal($memorySetID) . ' ' .					 
	 			   'ORDER BY CASE WHEN memoryitempriority.Category = '.dbVal($selectCategory).' THEN 0 ELSE 1 END, ' . 
                                        'memoryitempriority.NumCorrectInARow, RAND() * CorrectnessRatio ' .
				   'LIMIT 1';			
	  $memoryItemRow = getSQLRow($sql);  
	}
  
	/* Randomly select the direction based on the ForwardTestRatio of the memoryset. */
	$randFloat = randFloat();
	if ($randFloat <= $forwardTestRatio) {
	  $direction = FORWARD;
	} else {
            // Backward isn't working well right now.
	  //$direction = BACKWARD;            
            $direction = FORWARD;
	}        
        
	return array($memoryItemRow, $direction);
}

function randFloat() {
  return (float)rand() / (float)getrandmax ();
}

function printSound($text) {
    embedMaryGerman($text);
}

function getStringAlignment($str1, $str2) {    
  $len1 = strlen($str1);
  $len2 = strlen($str2);
  
  $gap_score = -2;
  
  $D = array_fill(0, $len1 + 1, array_fill(0, $len2 + 1, 0));
  // $D[i][j] = string 1 position i, string 2 position j
  
  // Base case, there is no penalty for aligning nothing with nothing
  $D[0][0] = 0;  

	// Initialize the first row and column
	for ($i = 0; $i <= $len1; $i += 1) {
		$D[$i][0] = $gap_score * $i;
	}  
	for ($j = 0; $j <= $len2; $j += 1) {
		$D[0][$j] = $gap_score * $j;
	}
	
	for ($i = 1; $i <= $len1; $i += 1) {
  	for ($j = 1; $j <= $len2; $j += 1) {
    	$match = $D[$i - 1][$j - 1] + char_similarity($str1[$i - 1], $str2[$j - 1]);
	    $gap2 = $D[$i][$j - 1] + $gap_score;
  	  $gap1 = $D[$i - 1][$j] + $gap_score;    	
			$D[$i][$j] = max($match, $gap2, $gap1);
	  }
	}  	

	$alignment = '';
	
	$i = $len1;
	$j = $len2;
	$score = 0;
	while ($i > 0 && $j > 0) {
	  $charSimilarity = char_similarity($str1[$i - 1], $str2[$j - 1]);
	  if ($D[$i][$j] - $charSimilarity == $D[$i - 1][$j - 1]) {
	    if ($charSimilarity > 0) {
	      // A matched character
	 	    $align = 'M';	 	    
	    } else {
	      // A changed character
	 	    $align = 'C';
	    }
	    
	    $i--;
	    $j--;
	    $score += $charSimilarity;
	  } else if ($D[$i][$j] - $gap_score == $D[$i][$j - 1]) {
	    // Skipped in str2, i.e. str1 added it
	    $align = 'A';
	    $j--;
	    $score += $gap_score;
	  } else if ($D[$i][$j] - $gap_score == $D[$i - 1][$j]) {
	    // Skipped in str1, i.e. str1 deleted it
	    $align = 'D';
	    $i--;	    
	    $score += $gap_score;	    
	  } else {
	    echo 'i = ' . $i . ', j = ' . $j . ' char sim = ' . $charSimilarity;
	    die ('Unexpected score in backtracking!');
	  }

	  $alignment = $align . $alignment;
	}
	
  while ($j > 0) {
    $alignment = 'A' . $alignment;
		$j--;
    $score += $gap_score;		
  }

  while ($i > 0) {
    $alignment = 'D' . $alignment;
		$i--;
    $score += $gap_score;		
  }
	
	return array($alignment, $score);
}

function char_similarity($chr1, $chr2) {
	if (strtoupper($chr1) == strtoupper($chr2)) {
	  return 1;
	} else {
		return -1;
	}  
}

function formatCue($cue) {
  return '<font face="Courier New" size=3 color="#555555">' . $cue . '</font>';
}

function formatGuessAndAnswer($guess, $answer) {     
  $guessFormat = '<font face="Courier New" size=3>';
	$answerFormat = '<font face="Courier New" size=3>';  	    
			  
	if ($guess == $answer) {
	  $guessFormat .= '<font color="#006600">' . $guess . '</font>';
	  $answerFormat .= '<font color="#006600">' . $answer . '</font>';
	} else if (trim($guess) == '') {
	  $guessFormat .= '';
	  $answerFormat .= '<font color="#000088">' . $answer . '</font>';
	} else {
	  list($alignment, $score) = getStringAlignment($guess, $answer);	  
	  
	  $minScore = - max(strlen($guess), strlen($answer)) / 2;
	  if ($score <= $minScore) {
	    $guessFormat .= '<font color="#BB0000">' . $guess . '</font>';
	    $answerFormat .= '<font color="#000077">' . $answer . '</font>';
	  } else {	  
		  /*
		  echo '<script>alert("' . $guess . '");</script>';
		  echo '<script>alert("' . $answer . '");</script>';
		  echo '<script>alert("' . $alignment . '");</script>';
		  */
		  
		  $len = strlen($alignment);
		  
		  $guessIndx = 0;
		  $answerIndx = 0;
		  for ($i = 0; $i < $len; $i++) {
		    if ($alignment[$i] == 'M') {
		      if ($guess[$guessIndx] != $answer[$answerIndx]) {
		        // They matched, but different case
			      $guessFormat .= '<font color="#BB0000">' . $guess[$guessIndx] . '</font>';
			    } else {
			      // Exact match
			      $guessFormat .= '<font color="#444444">' . $guess[$guessIndx] . '</font>';			      
			    }
		      $answerFormat .= '<font color="#444444">' . $answer[$answerIndx] . '</font>';
		      $guessIndx++;
		      $answerIndx++;      
		    } else if ($alignment[$i] == 'C') {
		      $guessFormat .= '<del><font color="#AA0000">' . $guess[$guessIndx] . '</font></del>';
		      $answerFormat .= $answer[$answerIndx];      
		      $guessIndx++;
		      $answerIndx++;
		    } else if ($alignment[$i] == 'D') {
		      $guessFormat .= '<del><font color="#BB0000">' . $guess[$guessIndx] . '</font></del>';
		      $answerFormat .= '_';      
		      $guessIndx++;
				} else if ($alignment[$i] == 'A') {
					$guessFormat .= '&nbsp;';
		      $answerFormat .= '<font color="#0000EE">' . $answer[$answerIndx] . '</font>';      
		      $answerIndx++;		  
				} else {
				  die('Unexpected alignment value!');
				}	   
			}
		}

		$guessFormat .= '</font>';
		$answerFormat .= '</font>';  	  		
	}

  return array($guessFormat, $answerFormat);
}

?>
