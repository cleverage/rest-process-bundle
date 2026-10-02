REST client
===========

A REST client holds the connection to one API (its base URI, the HTTP client to use) and sends the requests built by
the [RequestTask](tasks/request_task.md). Each client is a service tagged `cleverage.rest.client`, identified by a
unique code that the task references with its `client` option.

Client reference
----------------

* **Interface**: `CleverAge\RestProcessBundle\Client\ClientInterface`
* **Default implementation**: `CleverAge\RestProcessBundle\Client\Client`, based on the Symfony
  [HttpClient](https://symfony.com/doc/current/http_client.html)
* **Service tag**: `cleverage.rest.client`
* **Registry**: `CleverAge\RestProcessBundle\Registry\ClientRegistry` (service `cleverage_rest_process.registry.client`)

Constructor arguments
---------------------

Arguments of the default `CleverAge\RestProcessBundle\Client\Client`:

| Code          | Type                                                 | Required | Default | Description                                                                             |
|---------------|------------------------------------------------------|:--------:|---------|-----------------------------------------------------------------------------------------|
| `$httpClient` | `Symfony\Contracts\HttpClient\HttpClientInterface`   |  **X**   |         | HTTP client used to send the requests (e.g. `@http_client` or a scoped client)          |
| `$logger`     | `Psr\Log\LoggerInterface`                            |  **X**   |         | Logger used to log failed requests                                                      |
| `$code`       | `string`                                             |  **X**   |         | Unique code of the client, used by the `client` option of the task                      |
| `$uri`        | `string`                                             |  **X**   |         | Base URI of the API, without trailing `/` (e.g. `https://domain/api`)                   |

Request options
---------------

`ClientInterface::call(array $options)` receives the request options built by the [RequestTask](tasks/request_task.md)
(task options merged with the input). The default client resolves them with the following rules:

| Code             | Type                      | Required | Default            | Description                                                              |
|------------------|---------------------------|:--------:|--------------------|--------------------------------------------------------------------------|
| `url`            | `string`                  |  **X**   |                    | Path of the endpoint, appended to the base URI                           |
| `method`         | `string`                  |          | `GET`              | HTTP method (see below)                                                  |
| `sends`          | `string`                  |          | `application/json` | `Content-Type` header, not sent if empty                                 |
| `expects`        | `string`                  |          | `application/json` | `Accept` header, not sent if empty                                       |
| `url_parameters` | `array`                   |          | `[]`               | List of `placeholder => value` replaced in the URL                       |
| `headers`        | `array`                   |          | `[]`               | HTTP headers, as `name => value`                                         |
| `data`           | `array`, `string`, `null` |          | `null`             | Payload of the request (see below)                                       |

Any other key throws a `Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException`.

The request is then built this way:
- **URL**: `<base URI>/<url>`, the leading `/` of `url` being removed. Then each `{key}` of `url_parameters` is
  replaced by its value, converted to a string and encoded with
  [`rawurlencode()`](https://www.php.net/manual/en/function.rawurlencode.php) (a non-scalar value throws an
  `\UnexpectedValueException`).
- **Method**: must be one of `HEAD`, `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `TRACE`, `PATCH` (case-sensitive),
  otherwise a `CleverAge\RestProcessBundle\Exception\RestRequestException` (`<method> is not an HTTP method`) is thrown.
- **Headers**: `headers`, plus `Content-Type: <sends>` and `Accept: <expects>` when these options are not empty
  (they replace the same keys of `headers`).
- **Payload**, passed as a Symfony HttpClient
  [request option](https://symfony.com/doc/current/http_client.html#making-requests):

| `method`                | `sends`            | `data` is sent as | Result                                                         |
|-------------------------|--------------------|-------------------|----------------------------------------------------------------|
| `POST`, `PUT`, `PATCH`  | `application/json` | `json`            | JSON-encoded body                                              |
| `GET`                   | any                | `query`           | Query string parameters (`data` must be an `array` or `null`)  |
| any other combination   | any                | `body`            | Raw body (`string`), or form-urlencoded body (`array`)         |

If the HTTP client throws while creating the request, the error is logged (`Rest request failed`, with `url` and
`error`) and a `RestRequestException` is thrown. Note that Symfony HttpClient requests are lazy: most transport errors
only occur when the task reads the response.

Registration
------------

Every service tagged `cleverage.rest.client` is added to the registry (a compiler pass calls
`ClientRegistry::addClient()` for each of them, with the service id). Two clients with the same code throw an
`UnexpectedValueException` giving the ids of both services
(`Client <code> is already defined by service "<id>", cannot register service "<id>"`) when the registry is
instantiated; a task referencing an unknown code throws a
`CleverAge\RestProcessBundle\Exception\MissingClientException` (`No rest client with code : <code>`).

Implementing a client
---------------------

A client must implement `ClientInterface`:

| Method                                                | Description                                                                                  |
|-------------------------------------------------------|----------------------------------------------------------------------------------------------|
| `getCode(): string`                                   | Code of the client, used by the `client` option of the task. Must be unique                  |
| `geUri(): string`                                     | **Deprecated** (misspelled): base URI, use `getUri()` of the default `Client`                |
| `setUri(string $uri): void`                           | Change the base URI                                                                          |
| `call(array $options = []): ResponseInterface`        | Send the request described by the request options, return a Symfony HttpClient response      |

The simplest way is to extend the default `Client` and override one of its protected methods:
- `configureOptions(OptionsResolver $resolver)`: request options accepted by `call()`
- `getRequestOptions(array $options)`: Symfony HttpClient options (headers, `json`, `query`, `body`...)
- `getApiUrl()`: base URI used to build the request URL (defaults to `getUri()`)
- `constructUri(array $options)` / `replaceParametersInUri(string $uri, array $options)`: URL construction

Examples
--------

* Default client
  - with [autowiring](https://symfony.com/doc/current/service_container/autowiring.html) enabled (default
    `config/services.yaml`), `$httpClient` and `$logger` are injected automatically and only `$code` and `$uri` must be
    bound

```yaml
services:
  app.cleverage_rest_process.client.apicarto_ign:
    class: CleverAge\RestProcessBundle\Client\Client
    bind:
      $code: 'apicarto_ign'
      $uri: 'https://apicarto.ign.fr/api'
    tags:
      - { name: cleverage.rest.client }
```

* Default client without autowiring, using a
  [scoped HTTP client](https://symfony.com/doc/current/http_client.html#scoping-client) for timeouts and authentication

```yaml
# config/packages/framework.yaml
framework:
  http_client:
    scoped_clients:
      domain_sample.http_client:
        base_uri: 'https://domain'
        timeout: 10
        auth_bearer: '%env(DOMAIN_API_TOKEN)%'
```

```yaml
services:
  app.cleverage_rest_process.client.domain_sample:
    class: CleverAge\RestProcessBundle\Client\Client
    arguments:
      $httpClient: '@domain_sample.http_client'
      $logger: '@logger'
      $code: 'domain_sample'
      $uri: 'https://domain/api'
    tags:
      - { name: cleverage.rest.client }
```

* Custom client adding an API key header to every request

```php
<?php

declare(strict_types=1);

namespace App\Rest;

use CleverAge\RestProcessBundle\Client\Client;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiKeyClient extends Client
{
    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $code,
        string $uri,
        private readonly string $apiKey,
    ) {
        parent::__construct($httpClient, $logger, $code, $uri);
    }

    protected function getRequestOptions(array $options = []): array
    {
        $requestOptions = parent::getRequestOptions($options);
        $requestOptions['headers']['X-Api-Key'] = $this->apiKey;

        return $requestOptions;
    }
}
```

```yaml
services:
  app.cleverage_rest_process.client.domain_sample:
    class: App\Rest\ApiKeyClient
    arguments:
      $httpClient: '@http_client'
      $logger: '@logger'
      $code: 'domain_sample'
      $uri: 'https://domain/api'
      $apiKey: '%env(DOMAIN_API_KEY)%'
    tags:
      - { name: cleverage.rest.client }
```
