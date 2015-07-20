<?php 

function embedMaryGerman($input) {
    embedMaryTTS($input, 'de');
}

function embedMaryTTS($input, $locale) {
	$key = getenv('MARYTTS_HMAC_SECRET');
	$keyBytes = base64_decode($key);

	$baseUrl = getenv('MARYTTS_URL');
	
	$src = $baseUrl . '?';
		
	$expires = time() + 120;
	$stringToSign = $input . $locale . $expires;
	
	$signatureBytes = hash_hmac ("sha256", $stringToSign, $keyBytes, true);
	$signature = base64_encode($signatureBytes);
	
	$src .= "text=" . urlencode($input);
	$src .= "&locale=" . urlencode($locale);
  $src .= "&expires=" . urlencode($expires);
  $src .= "&signature=" . urlencode($signature);

?>
        <audio autoplay>
            <source src="<?php echo($src); ?>" type="audio/wav">
            <embed height="100" width="100" src="<?php echo($src); ?>" type="audio/x-wav" autostart=true loop=1 >
        </audio>
        </span>
<?php	
}

?>
