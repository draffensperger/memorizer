function specialCharBoxKeyDown(evt, input) {
}

function specialCharBoxKeyPress(evt, input) {
}

function specialCharBoxKeyUp(evt, input) {
  var substs = [
    ["'C", "\u00C7"],
    ["'e", "\u00E9"],
    ["'y", "\u00FD"],
    ["'u", "\u00FA"],
    ["'i", "\u00ED"],								
    ["'o", "\u00F3"],			
    ["'a", "\u00E1"],		
    ['"e', '\u00EB'],		
    ['"u', '\u00FC'],
    ['"U', '\u00DC'],
    ['"i', '\u00EF'],		
    ['"o', '\u00F6'],
    ['"O', '\u00D6'],
    ['"a', '\u00E4'],
    ['"A', '\u00C4'],
    ['`e', '\u00E8'],		
    ['`u', '\u00F9'],		
    ['`i', '\u00EC'],		
    ['`o', '\u00F2'],
    ['~o', '\u00F5'],		
    ['~n', '\u00F1'],		
    ['^e', '\u00EA'],		
    ['^u', '\u00FB'],
    ['^i', '\u00EE'],
    ['^o', '\u00F4'],
    ['^a', '\u00E2'],						
    ['$s', '\u00DF']
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
