<?php

include 'util.php';

while (list($name, $value) = each($HTTP_POST_VARS)) { $input[$name] = stripslashes($value); }
while (list($name, $value) = each($HTTP_GET_VARS)) { $input[$name] = stripslashes($value); }
$memorySetID = $input['MemorySetID'];
	 
function printSiteHeader($whichSection, $bodyOnLoad = '', $requiredFields = array(), $additionalValidation = '') {     
   global $section;
   $section = $whichSection;	 
   
   global $memorySetID;

//<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//FR" "http://www.w3.org/TR/html4/loose.dtd">	      
   ?>
<HTML>
<HEAD>
   <TITLE>Memorizer</TITLE>
   <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta name="robots" content="all">
  <LINK href="style.css" rel="stylesheet" type="text/css">
 	 <script language="javascript" src="specialChars.js">
 	 </script>
   <script language="javascript">      
      function validate(formObject) {
		  <?php
		  foreach ($requiredFields as $requiredField) {
		    $prettyField = ucwords(str_replace("_", " ", $requiredField));
		    ?>

		    if (isUndefined(formObject.<?=$requiredField?>.value)) {
			  	if (!validateRadioButton(formObject.<?=$requiredField?>, '<?=$prettyField?>')) {
			  	  return false;
			  	}   		      	
   		    }   		    
   		    
			if (formObject.<?=$requiredField?>.value == '') {
   		      	alert('Please enter a value for <?=$prettyField?>');				  
		      	formObject.<?=$requiredField?>.focus();
		      	return false;
		    }		    
		    
		    <?php
		  }		  		  
		  ?>
		  
		  <?=$additionalValidation?>
		  
		  return true;
	  }
	  
	  function validateRadioButton(radioButton, prettyField) {
			var isOptionSelected = false;
			
			for (i=radioButton.length-1; i > -1; i--) {
				if (radioButton[i].checked) {
					isOptionSelected = true;
					break;
				}
			}
			
			if (!isOptionSelected) {
			    alert('Please select a value for ' + prettyField);   		      	   	
		      	radioButton[0].focus();		      	
				return false;
			}
			
			return true;
	  }
	  
	  function isUndefined(a) {
     return typeof a == 'undefined';
	  }       
   </script>
</HEAD>

<BODY onload="<?=$bodyOnLoad?>">
<center>
<table cellspacing="0" cellpadding="0" border="0" width="730">
<tr>
   <td width="100%" colspan=2>
   <img src="images/banner.png">
   </td>
</tr>
<tr>
   <td valign=top align="left" width="155" id="menutd">
	   	<div id="header">
		<ul id="menu">
			<li class="<?php echo sectionType('home'); ?>"><a href="index.php">Select List</a></li>
			<li class="<?php echo sectionType('test'); ?>">
				<a href="test.php?MemorySetID=<?=$memorySetID?>">Test</a></li>						
			<li class="<?php echo sectionType('add'); ?>">
				<a href="add.php?MemorySetID=<?=$memorySetID?>">Add Item</a></li>						
			<li class="<?php echo sectionType('batch_add'); ?>">
				<a href="batch_add.php?MemorySetID=<?=$memorySetID?>">Batch Add</a></li>							
			<li class="<?php echo sectionType('view'); ?>">
				<a href="view.php?MemorySetID=<?=$memorySetID?>">View Items</a></li>			
			<li class="<?php echo sectionType('stats'); ?>">
				<a href="stats.php?MemorySetID=<?=$memorySetID?>">Statistics</a></li>									
			<li class="<?php echo sectionType('about'); ?>">
				<a href="about.php">About Us</a></li>
		</ul>
		&nbsp;
		</div>
   </td>
   <td align="left" width="835" valign="top" id="contenttd" height="100%">
   <?php
}              

function sectionType($which) { 
   global $section;
   if ($section == $which) {
      echo 'current';
   } else {
      echo 'normal';
   }
}

function printSiteFooter() {  
   ?>
	</td>
</tr>
<tr>
 <td colspan=2 style="color: black" id="footertd">
 Copyright 2006 by David Raffensperger
 </td>
</tr>
</table>
</center>
</BODY>
</HTML>
   
<?php
}
?>
