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
	  //printCurrentMemoryCuePage($input);
	  $input['MemorySetID'] = getSQLScalar('SELECT MemorySetID FROM memoryitem WHERE MemoryItemID = ' . $input['MemoryItemID']);
	  printNextMemoryCuePage($input);
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

function printStatistics($input) {
  $memorySetID = $input['MemorySetID'];
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
	echo '<br>';
	echo '</small>';
}

function printNextMemoryCuePage($input) {
  printPageHeader(TEST_FORM_BODY_ONLOAD);  
  printStatistics($input);
  
  $memorySetID = $input['MemorySetID'];

  $nextMemoryItemAndDir = getNextMemoryItemAndDirection(getUserID(), $memorySetID);
  $nextMemoryItem = $nextMemoryItemAndDir[0];

	printMemoryCue($nextMemoryItemAndDir);

  printPageFooter();
}

function printCurrentMemoryCuePage($input) {
  printPageHeader(TEST_FORM_BODY_ONLOAD);  
	$memoryItemID = $input['MemoryItemID'];

  //$direction = $input['direction'];
  echo 'Not supposed to be here!!!';

  $nextMemoryItemAndDir = getMemoryItemAndDirectionFromID($memoryItemID, $direction);
  $nextMemoryItem = $nextMemoryItemAndDir[0];

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
	
	printStatistics($input);
  
  if ($isCorrect) {
    $nextMemoryItemAndDir = getNextMemoryItemAndDirection(getUserID(), $memorySetID);
    $nextMemoryItem = $nextMemoryItemAndDir[0];  
    
    if ($nextMemoryItem['MemoryItemID'] != $memoryItemID) {
    	printResultTable($isCorrect, $cue, $guess, $correctAnswer, $memoryItemID);
    	printSound($correctAnswer);
    } else {
      echo '<h1>Correct, but keep practicing.</h1>';
    }
    
	  printMemoryCue($nextMemoryItemAndDir);
    echo '<script language="JavaScript">document.memoryTestForm.guess.focus();</script>';
	} else {
	  $memoryItem = getMemoryItemFromID($memoryItemID);
	  printResultTable($isCorrect, $cue, $guess, $correctAnswer, $memoryItemID);
   	printSound($correctAnswer);	  
		printRetryForm($memoryItemID, $direction);
	}	
	
  printPageFooter();	
}

/******************************** Print Utility Functions *****************************************/

function printResultTable($isCorrect, $cue, $guess, $correctAnswer, $memoryItemID) {
  if ($isCorrect) {
	  $correctnessString = 'Correct';	    
  } else {
	  $correctnessString = 'Incorrect';	    
  }  	

  list($guessFormatting, $answerFormatting) = formatGuessAndAnswer($guess, $correctAnswer);
	
  ?>
  <h1>Previous result: <?=$correctnessString?></h1>
	<table>
	<tr><td><span class="bigletters">Cue</span></td><td><span class="bigletters"><?=formatCue($cue)?></span></td></tr>
	
	<tr><td><span class="bigletters">Guess</span></td><td><span class="bigletters"><?=$guessFormatting?></span></td></tr>
	<tr><td><span class="bigletters">Answer</span></td><td><span class="bigletters"><?=$answerFormatting?>
			
			&nbsp;&nbsp;<a href="edit.php?MemoryItemID=<?=$memoryItemID?>">Edit</a>
			
			&nbsp;<a href="http://www.dict.cc/?s=<?=urlencode($correctAnswer)?>">dict.cc</a>
			
			</span>
			</td></tr>
		
	</table>
	<?php
}

function printRetryForm($memoryItemID, $direction) {	  
  ?>
  <form name="retryForm" method="POST" action="test.php">
  <input type="submit" name="retestButton" value="Retest">
  <script language="JavaScript">document.retryForm.retestButton.focus();</script>
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
<span class="bigletters"><?=$cue?></span>
</td>
</tr>
<tr>
<td>
<span class="bigletters">
<input type="text" name="guess" maxlength="255" class="bigletters" style="width:780px" autocomplete="off" 
	onkeydown="specialCharBoxKeyDown(event, document.memoryTestForm.guess);"
	onkeypress="specialCharBoxKeyPress(event, document.memoryTestForm.guess);"	
	onkeyup="specialCharBoxKeyUp(event, document.memoryTestForm.guess);">
</span>	
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
function getNextMemoryItemAndDirection($userID, $memorySetID) {  
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
	
	if ($numNeedsPractice > 0) {
	  $selectCategory = 'NeedsPractice';
	} else {
	  $probTestUnlearned = $numUnlearned / $workingSetSize;
	  
	  if ($numUntested > 0) {    
		  $probTestUntested = (1 - $probTestUnlearned) * $newVocabRatio;
		} else {
		  echo 'No ones untested..';
		  $probTestUnlearned = $probTestUnlearned + (1 - $probUnlearned) * $newVocabRatio;
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
  $sql = 'SELECT memoryitem.MemoryItemID, memoryitem.MemorySetID, ' . 
				 'memoryitem.CueText, memoryitem.DataText ' .
 			   'FROM memoryitempriority ' . 
 			   'INNER JOIN memoryitem ON memoryitem.MemoryItemID = memoryitempriority.MemoryItemID ' . 
			   'WHERE (memoryitempriority.UserID IS NULL OR memoryitempriority.UserID = ' . dbVal($userID) . ')' .  
				 ' AND memoryitempriority.MemorySetID = ' . dbVal($memorySetID) . ' ' .
				 ' AND memoryitempriority.Category = ' . dbVal($selectCategory) . ' ' . 
 			   'ORDER BY memoryitempriority.NumCorrectInARow, RAND() * CorrectnessRatio ' .
			   'LIMIT 1';			
  $memoryItemRow = getSQLRow($sql);  
  
	/* Randomly select the direction based on the ForwardTestRatio of the memoryset. */
	$randFloat = randFloat();
	if ($randFloat <= $forwardTestRatio) {
	  $direction = FORWARD;
	} else {
	  $direction = BACKWARD;
	}
    
	return array($memoryItemRow, $direction);
}

function randFloat() {
  return (float)rand() / (float)getrandmax ();
}

function printSound($text) {
	/*
  $maryXML = 
		'<maryxml version="0.4" '.
		' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . 
		' xmlns="http://mary.dfki.de/2002/MaryXML"'.
	  ' xml:lang="de">' . 
	  '<prosody volume="loud">' . 
	  $text . 
	  '</prosody>' .
		'</maryxml>';
  echo '<embed src="mary_client.php?in=RAWMARYXML&voice=de6&text=' . urlencode($maryXML) . '" type="audio/x-wav" ' .
		'autostart=true loop=1 height=50 width=300>';
		*/
	
	/*
	I had the idea of doing caching of sounds, but that will take too much space
	$soundfile = 'sounds/' . $text . '.wav';
	if (file_exists($soundfile)) {
	  $src = $soundfile;
	} else {
		$src = 'mary_client.php?in=TEXT_DE&voice=de6&outfile=' . urlencode($soundfile) . '&text=' . urlencode($text);
	}
	*/
	
	$src = 'mary_client.php?in=TEXT_DE&voice=de6&text=' . urlencode($text);
	echo '<embed src="' . $src . '" type="audio/x-wav" autostart=true loop=1 height=50 width=300>';		
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

	$alginment = '';
	
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
    $alignment = $alignment . 'D';
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
		      $answerFormat .= '&nbsp;';      
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
