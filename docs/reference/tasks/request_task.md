RequestTask
===========

Sends an HTTP request through a registered [REST client](../client.md) and outputs the raw response body.

The request is built from the task options, optionally overridden by the input: this allows calling the same endpoint
for each item of a flow (e.g. one `PUT` per CSV line) with a different URL, parameters or payload.

Task reference
--------------

* **Service**: `CleverAge\RestProcessBundle\Task\RequestTask`

Accepted inputs
---------------

`array` or empty value (`null`, `[]`...): request options overriding the task options (shallow merge, the input wins).
Allowed keys are `url`, `method`, `headers`, `url_parameters`, `sends`, `expects` and `data`: any other key is passed to
the client, which rejects it (the default client throws an `UndefinedOptionsException`). Any other non-empty input
(e.g. a `string`) throws an `\UnexpectedValueException`.

Possible outputs
----------------

`string`: the body of the response (empty string for a `204 No Content`), as returned by
`Symfony\Contracts\HttpClient\ResponseInterface::getContent(false)`. It is not decoded: chain a
[DeserializerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/deserializer_task.md) or a
[TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md) to
decode it.

When the status code is not in `valid_response_code`, the raw response body is sent to the `error_outputs` (see
[Notes](#notes)).

Options
-------

| Code                  | Type                      | Required | Default            | Description                                                                                                                                    |
|-----------------------|---------------------------|:--------:|--------------------|------------------------------------------------------------------------------------------------------------------------------------------------|
| `client`              | `string`                  |  **X**   |                    | Code of the [REST client](../client.md) to use (value returned by its `getCode()`)                                                             |
| `url`                 | `string`                  |  **X**   |                    | Path of the endpoint, appended to the client base URI (a leading `/` is optional). May contain `{placeholders}` replaced by `url_parameters`   |
| `method`              | `string`                  |  **X**   |                    | HTTP method, in uppercase, among `HEAD`, `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `TRACE`, `PATCH` (checked by the default client)          |
| `headers`             | `array`                   |          | `[]`               | HTTP headers, as `name => value`                                                                                                               |
| `url_parameters`      | `array`                   |          | `[]`               | List of `placeholder => value`: each `{placeholder}` of the URL is replaced by the URL-encoded value (scalar values, converted to strings)     |
| `data`                | `array`, `string`, `null` |          | `null`             | Payload of the request, sent as JSON body, query string or raw body depending on `method` and `sends` (see [REST client](../client.md#request-options)) |
| `sends`               | `string`                  |          | `application/json` | Value of the `Content-Type` header (not sent if empty)                                                                                         |
| `expects`             | `string`                  |          | `application/json` | Value of the `Accept` header (not sent if empty)                                                                                               |
| `valid_response_code` | `array`                   |          | `[200, 201, 204]`  | List of the [HTTP status codes](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes) considered as a success                               |
| `log_response`        | `bool`                    |          | `false`            | Log the requested URL, the request options, and the status code, headers and content of the response (`debug` level)                           |

Options are resolved once per process execution: [contextual values](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#contextual-values)
like `'{{ code }}'` (passed with `-c code:"'value'"`) are allowed in any option. Use the input to change the request for
each item.

Examples
--------

* `GET` with a URL parameter taken from the process context
  - run with `bin/console cleverage:process:execute <process> -c codePostal:"'46800'"`
  - requests `https://apicarto.ign.fr/api/codes-postaux/communes/46800` with the client below

```yaml
services:
  app.cleverage_rest_process.client.apicarto_ign:
    class: CleverAge\RestProcessBundle\Client\Client
    arguments:
      $httpClient: '@http_client'
      $logger: '@logger'
      $code: 'apicarto_ign'
      $uri: 'https://apicarto.ign.fr/api'
    tags:
      - { name: cleverage.rest.client }
```

```yaml
# Task configuration level
entry:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  error_strategy: stop
  options:
    client: apicarto_ign
    url: '/codes-postaux/communes/{codePostal}'
    method: GET
    url_parameters: { codePostal: '{{ codePostal }}' }
  outputs: [deserialize]
```

* `GET` with query string parameters
  - requests `https://domain/api/books?page=2&limit=50`

```yaml
# Task configuration level
list_books:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  options:
    client: domain_sample
    url: '/books'
    method: GET
    data:
      page: 2
      limit: 50
  outputs: [deserialize]
```

* `POST` with a JSON body
  - `data` is JSON-encoded because the method is `POST` and `sends` is `application/json`

```yaml
# Task configuration level
entry:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  error_strategy: stop
  options:
    client: apicarto_ign
    url: '/aoc/appellation-viticole'
    method: POST
    data:
      geom:
        type: Point
        coordinates: [-1.691634, 48.104237]
  outputs: [deserialize]
```

* `POST` of a form, with an authentication header
  - `data` is sent as an `application/x-www-form-urlencoded` body

```yaml
# Task configuration level
get_token:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  options:
    client: domain_sample
    url: '/oauth/token'
    method: POST
    sends: 'application/x-www-form-urlencoded'
    headers:
      Authorization: 'Basic {{ credentials }}'
    data:
      grant_type: client_credentials
  outputs: [deserialize]
```

* Request overridden by the input
  - the previous task outputs `{url_parameters: {id: '42'}, data: {title: 'New title'}}`
  - requests `PUT https://domain/api/books/42` with the JSON body `{"title": "New title"}`
  - a `404 Not Found` is logged and the item is skipped, the process goes on with the next one

```yaml
# Task configuration level
update_book:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  error_strategy: skip
  options:
    client: domain_sample
    url: '/books/{id}'
    method: PUT
  error_outputs: [log_error] # Receives the raw response body
```

Notes
-----

* The input is merged with `array_merge()`: an input key replaces the whole option (e.g. an input `url_parameters`
  replaces the configured `url_parameters`, it is not merged with it). `client`, `valid_response_code` and
  `log_response` cannot be overridden by the input.
* When the status code is not in `valid_response_code`, the task sets the raw response body as error output, then
  fails with an `Invalid response code` exception, logged with the response headers and body. The process then follows
  the task `error_strategy`: `skip` goes on with the next item, `stop` stops the process.
* A `3xx`, `4xx` or `5xx` status code listed in `valid_response_code` is handled as a success: the response body is
  output. Redirections are followed by the HTTP client, so a `3xx` status is only received when redirections are
  disabled or exceeded.
* A transport error (DNS failure, timeout...) makes the task fail as well: it is logged (`REST request failed`, without
  the response headers and body) then thrown. An unknown `method` makes the task fail too.
* A `client` that is not registered throws a `CleverAge\RestProcessBundle\Exception\MissingClientException`
  (`No rest client with code : <code>`) on the first execution of the task.
