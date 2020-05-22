<?php 

require 'vendor/autoload.php';
use Google\Cloud\PubSub\PubSubClient;
// Archivo json proporcionado por Jim Sports
$json_file = 'archivo.json';
$json_content = (array)json_decode(file_get_contents($json_file));

// Suscripciones
$suscriptions = $json_content["client_suscriptions"];

putenv('GOOGLE_APPLICATION_CREDENTIALS='.$json_file);

$pubSub = new PubSubClient([
	'projectId' => $json_content["project_id"]
]);

// Recorrer las suscripciones
foreach ($suscriptions as $suscription) {

	// Nombre de la suscripción proporcionada por Jim Sports
	$subscription = $pubSub->subscription($suscription);

	// Descargar todos los mensajes pendientes
	$messages = $subscription->pull();

	// Recorrer los mensajes descargados
	foreach ($messages as $message) {

		// El mensaje llega comprimido y en formato json
		$data = gzuncompress($message->data());
		$data = json_decode($data, true);		

		// Array de atributos [object, timestamp, action] y opcionalmente [parts]
		$attributes = $message->attributes();

		// Id del mensaje
		$messageId = $message->id();
		
		// Array con la información
		var_dump($data);

		// Marcar mensaje como leído
		//$subscription->acknowledge($message);

	}

}

?>