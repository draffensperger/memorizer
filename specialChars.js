function specialCharBoxKeyDown(evt, input) {
}

function specialCharBoxKeyPress(evt, input) {
}

/*
' (APOSTROPHE)  	C  	Ç
'(APOSTROPHE) 	e, y, u, i, o, a , ý, ú, í, ó, á
"(QUOTATION MARK) 	e, u, i, o, a 	ë, ü, ï, ö, ä
`(ACCENT GRAVE) 	e, u, i, o 	è, ù, ì, ò
~(TILDE) 	o, n 	õ, ñ
^(CARET) 	e, u, i, o, a 	ê, û, î, ô, â
$(DOLLAR) s ß
*/
function specialCharBoxKeyUp(evt, input) {
  var substs = [
		["'C", "Ç"],
		["'e", "é"],
		["'y", "ý"],
		["'u", "ú"],
		["'i", "í"],								
		["'o", "ó"],			
		["'a", "á"],		
		['"e', 'ë'],		
		['"u', 'ü'],
		['"U', 'Ü'],
		['"i', 'ï'],		
		['"o', 'ö'],
		['"O', 'Ö'],
		['"a', 'ä'],
		['"A', 'Ä'],
		['`e', 'è'],		
		['`u', 'ù'],		
		['`i', 'ì'],		
		['`o', 'ò'],
		['~o', 'õ'],		
		['~n', 'ñ'],		
		['^e', 'ê'],		
		['^u', 'û'],
		['^i', 'î'],
		['^o', 'ô'],
		['^a', 'â'],						
		['$s', 'ß']
	];
  
	var lastChars = input.value.substring(input.value.length - 2, input.value.length);

	var i;
	for (i = 0; i < substs.length; i++) {
	  if (substs[i] != undefined && lastChars == substs[i][0]) {
	    input.value = input.value.substring(0, input.value.length - substs[i][0].length) + substs[i][1];
	    break;
	  }
	}
}