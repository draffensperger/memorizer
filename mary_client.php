<?php
# This code calls the Mary TTS Server to to Text-to-Speech
# See the appropriate licenses for the Mary system.

header('Content-type: audio/wav');
header('Content-disposition: inline');

# There will be warnings saying that you should not connect
# to the same IP address and port twice with two different sockets, but
# this is necessary for communicating with the Mary TTS server.
error_reporting(1);

while (list($name, $value) = each($HTTP_POST_VARS)) { $input[$name] = stripslashes($value); }
while (list($name, $value) = each($HTTP_GET_VARS)) { $input[$name] = stripslashes($value); }

#$host = "cling.dfki.uni-sb.de";
$host = "localhost";
$port = 59125;

$in  = "TEXT_DE";
if ($input['in'] != '') {
  $in = $input['in'];
}

$out = "AUDIO";
#$audiotype = "MP3";
$audiotype = "WAVE";
#$voice = "male";
$voice = "de6";
if ($input['voice'] != '') {
  $voice = $input['voice'];
}

$outfilename = $input['outfile'];
if ($outfilename != '') {
  $outfile = fopen($outfilename, "w+");
}

$text = $input['text'];

$socket_timeout_sec = 0;
$socket_timeout_usec = 300;

$ip = gethostbyname($host);

# create a tcp connection to the specified host and port
$maryInfoSocket = socket_create(AF_INET, SOCK_STREAM, 0);
stream_set_timeout($maryInfoSocket, $socket_timeout_sec, $socket_timeout_usec);
socket_bind($maryInfoSocket, $ip, $port);
socket_connect($maryInfoSocket, $ip, $port);

# avoid buffering when writing to server:

########## Write input to server: ##########
# formulate the request:
$msg = "MARY IN=$in OUT=$out AUDIO=$audiotype";
if ($voice) { 
  $msg .= " VOICE=$voice"; 
}
$msg .= "\015\012";

# Send our format request
socket_send($maryInfoSocket, $msg, strlen($msg), 0);

# Get back the id
socket_recv($maryInfoSocket, $id, 32, 0);

# open second socket for the data:
$maryDataSocket = socket_create(AF_INET, SOCK_STREAM, 0);
stream_set_timeout($maryDataSocket, $socket_timeout_sec, $socket_timeout_usec);
socket_bind($maryDataSocket, $ip, $port);
socket_connect($maryDataSocket, $ip, $port);

# identify with request number, and send the text to dictate.
$msg = $id . "\015\012" . utf8_encode($text) . "\015\012";
socket_send($maryDataSocket, $msg, strlen($msg), 0);

# shutdown the sending part of the socket so that the Mary server
# knows to start sending back the response.
socket_shutdown($maryDataSocket, 1);
while (socket_recv($maryDataSocket, $buffer, 1024, 0) > 0) {
  echo $buffer;
  
	if ($outfilename != '') {  
	  fputs($outfile, $buffer);
	}
}

fclose($maryDataSocket);
if ($outfilename != '') {  
  fclose($outfile);
}

$erorr_file = fopen("mary_error.txt", "a+");
while (socket_recv($maryInfoSocket, $buffer, 1024, 0) > 0) {
  fputs($erorr_file, date(DATE_RFC822) . $text . ": " . $buffer);  
}

fputs($erorr_file, $outfilename);  
fclose($erorr_file);

# close the info socket - we should retrieve the warnings and 
# errors from it, but we won't bother for this attempt.
fclose($maryInfoSocket);

?>
