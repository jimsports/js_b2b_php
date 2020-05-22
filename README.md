Sincronización de datos para clientes de Jim Sports
============================================================

En este artículo dispone de intrucciones para poder recibir información desde Jim Sports relativa a producto, de forma automática.

Para ello, se utiliza el servicio de distribución de información Google Pub/Sub. Este es un servicio de mensajería asíncrono que además ofrece almacenamiento de mensajes y entrega en tiempo real, con alta disponibilidad y rendimiento a gran escala.

Más información sobre `Google Pub/Sub <https://cloud.google.com/pubsub/>`_



## Información en forma de paquetes

El sistema envía paquetes de información desde sus servidores al cliente. Este envío puede realizarse de forma síncrona o asíncrona.

Los paquetes serán enviados de forma reiterada hasta que su sistema le comunique a Google que ha consumido correctamente el mensaje. En cuanto esto pase, el mensaje dejará de estar disponible y será eliminado de la cola.

## Suscripciones

Como cliente de Jim Sports usted estará suscrito a dos temas (topics). Gracias a estas suscripciones recibirá paquetes de información.



Paquetes
-------------------------------------------------
Para funcionar en modo asíncrono, usted deberá ejecutar el script de descarga de datos manualmente o mediante una tarea Cron o similar.

Para funcionar en modo síncrono, debe proporcionar a Jim Sports la url en la que se encuentra su código (endpoint) que ejecutaremos en tiempo real.

En cualquier caso, este sistema no asegura que los paquetes sean entregados de forma ordenada.

Todos los paquetes se subministrarán comprimidos y en formato json.



## Instrucciones

Para poder recibir información es necesario disponer de unas credenciales de acceso que le serán facilitadas por Jim Sports. Estas credenciales se le entregarán por correo electrónico, en un fichero con extensión json.



Modo asíncrono
-------------------------------------------------
Debe crear un fichero php que se encargará de recibir los paquetes y procesarlos.



### Paso 1 - Instalar librerías de Google Pub/Sub 

La mejor forma de instalar esta librería es utilizar el gestor de librerías Composer.

$ composer require google/cloud-pubsub



### Paso 2 - Crear el fichero PHP para la recepción de mensajes 

Cree un fichero PHP con el siguiente contenido:

```
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

		// Marcar mensaje como leído (consumirlo)

		$subscription->acknowledge($message);

	}

}
```

### Paso 3 - Recorrer los mensajes 

Cuando usted recorra los mensajes, puede decidir consumirlo o no. Debe tener presente que el orden de los mensajes puede no ser el correcto, por lo que quizás prefiera no consumir un mensaje antes que otro. 

Un ejemplo básico: recibe un mensaje de creación de producto, asignado a una categoría de la que aún no dispone. En este caso, debe obviar el mensaje a la espera de recibir un mensaje de creción de esa categoría.

Una vez usted quiera dejar de recibir el mensaje, debe "consumirlo".




Modo síncrono
-------------------------------------------------
Debe crear un fichero php (endpoint) que se encargará de recepcionar los paquetes y procesarlos. Deberá notificar a Jim Sports la url de este endpoint. 



### Paso 1 - Instalar librerías de Google Pub/Sub 


La mejor forma de instalar esta librería es utilizar el gestor de librerías Composer.

$ composer require google/cloud-pubsub

Además necesitará instalar la librería apiclient de google:

$ composer require google/apiclient



### Paso 2 - Crear el fichero PHP para la recepción de mensajes (endpoint)

Cree un fichero PHP con el siguiente contenido:

```
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

		http_response_code(202);		

	}

}
```


### Paso 3 - Recibir el mensaje

Al igual que en el modo asíncrono, usted puede decidir consumirlo o no. Debe tener presente que el orden de los mensajes puede no ser el correcto, por lo que quizás prefiera no consumir un mensaje antes que otro. 

Un ejemplo básico: recibe un mensaje de creación de producto, asignado a una categoría de la que aún no dispone. En este caso, debe obviar el mensaje a la espera de recibir un mensaje de creción de esa categoría.

Una vez usted quiera dejar de recibir el mensaje, debe "consumirlo" retornando un código http 202. Mientras esto no suceda, el sistema seguirá enviándole mensajes casi en tiempo real.

