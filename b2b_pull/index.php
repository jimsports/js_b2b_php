<?php 
require 'vendor/autoload.php';
use Google\Cloud\PubSub\PubSubClient;
// Archivo json proporcionado por Jim Sports
$json_file = 'archivo.json';
$json_content = (array)json_decode(file_get_contents($json_file));

$headers = getallheaders();
$content = json_decode(file_get_contents("php://input"), true);

if(isset($headers['Authorization'])){

	$token = str_replace("Bearer ", "", $headers['Authorization']);

	$client = new Google_Client(); 
	$client->setAuthConfig($json_content);

	// Verificar mediante token
	if($client->verifyIdToken($token)){

		// Mensaje
		$mensaje = $content['message'];

		// Id del mensaje
		$messageId = $mensaje["messageId"];

		// Atributos
		$attributes = $mensaje["attributes"];

		// Timestamp
		$timestamp = $attributes["timestamp"];
		
		// El mensaje llega comprimido y en formato json
		$data = gzuncompress(base64_decode($mensaje["data"]));
		$data = json_decode($data, true);	
		
		// Marcar mensaje como leído (consumirlo)
		// http_response_code(202);		

	}

}
?>