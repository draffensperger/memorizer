<?php 

function embedMaryGerman($input) {
    embedMaryTTS($input, 'de');
}

function embedMaryTTS($input, $locale) {
	$user = 'mem';
	$key = 'RSpDFAaoQMGRzjU9cjzIl+jn0lrzeFdZMfkDN3Z0C9jOw+2irzht+wJ3pHec/9BoA4R62KaotVad95zhAcJ7Sg==';
	$keyBytes = base64_decode($key);

	$baseUrl = 'http://tts.aws.af.cm/';
	#$baseUrl = 'http://localhost:8080/'
	
	$src = $baseUrl . '?';
		
	$expires = time() + 120;
	$stringToSign = $input . $locale . $expires;
	$bytesToSign = utf8_encode($stringToSign);
	
	$signatureBytes = hash_hmac ("sha1", $bytesToSign, $keyBytes, true);
	$signature = base64_encode($signatureBytes);
	
	$src .= "t=" . urlencode($input);
	$src .= "&l=" . urlencode($locale);
	$src .= "&e=" . urlencode($expires);
	$src .= "&u=" . urlencode($user);	
	$src .= "&s=" . urlencode($signature);
		
        ?>
        <span style="display:none">
        <audio autoplay>            
            <source src="<?php echo($src); ?>" type="audio/wav">
            <embed height="100" width="100" src="<?php echo($src); ?>" type="audio/x-wav" autostart=true loop=1 >
        </audio>
        </span>
        <?php	
}

?>