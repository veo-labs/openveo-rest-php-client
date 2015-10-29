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

$OPENVEO_WEB_SERVICE_HOST = 'Openveo web service host';
$OPENVEO_WEB_SERVICE_PORT = 'Openveo web service port';

// Instanciate client with client id, client secret, host (e.g. localhost) and port (e.g. 3001)
$client = new OpenveoClient(CLIENT_ID, CLIENT_SECRET, $OPENVEO_WEB_SERVICE_HOST, $OPENVEO_WEB_SERVICE_PORT);

// Build query parameters
$param = [
  'limit' => 2,
  'page' => 0,
  'sortBy' => 'date',
  'sortOrder' => 'asc',
  'properties' => [
    'Custom property 1' => 'plop'
  ]
];
$query = http_build_query($param);

// Build url
$url = 'http://' . $OPENVEO_WEB_SERVICE_HOST . ':' . $OPENVEO_WEB_SERVICE_PORT . '/publish/videos?' . $query;

// Got results
$results = $client->get($url);
var_dump($results);

?>
```

# Contributors

Maintainer : [Veo-Labs](http://www.veo-labs.com/)

# License

[AGPL](http://www.gnu.org/licenses/agpl-3.0.en.html)