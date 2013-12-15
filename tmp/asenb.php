<?php
ini_set('display_errors', 1);
$ch = curl_init();

$type = $_POST['type'];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
switch ($type) {
	case 'login':
		curl_setopt($ch, CURLOPT_URL, 'http://dnk.en.cx/login/signin?json=1');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'login=alex-soft&password=colorless22');
		break;
	case 'info':
		curl_setopt($ch, CURLOPT_URL, 'http://dnk.en.cx/gameengines/encounter/play/43004?json=1');
		break;
}

$content = curl_exec($ch);


$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($content, 0, $header_size);
$body = substr($content, $header_size);

// $header = curl_getinfo($ch);
curl_close($ch);

// echo $content;
var_dump($header);

?>