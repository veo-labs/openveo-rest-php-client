# OpenVeo PHP Client

OpenVeo PHP REST client to interact with [OpenVeo](https://github.com/veo-labs/openveo-core) Web Service.

# Documentation

## Installation

Install the latest version with :

    composer require openveo/rest-php-client

## Usage

```php
<?php

use Openveo\Client\Client as OpenveoClient;

$host = 'Openveo web service host';
$port = 'Openveo web service port';
$clientId = 'You application client id generated by OpenVeo';
$clientSecret = 'You application client secret generated by OpenVeo';

// Instanciate client with client id, client secret, host (e.g. localhost) and port (e.g. 3001)
$client = new OpenveoClient($clientId, $clientSecret, $host, $port);

// Build url
$url = 'http://' . $host . ':' . $port . '/webServiceEndPoint';

// Make web service call
$results = $client->get($url);
var_dump($results);

?>
```

# Contributors

Maintainer : [Veo-Labs](http://www.veo-labs.com/)

# License

[AGPL](http://www.gnu.org/licenses/agpl-3.0.en.html)