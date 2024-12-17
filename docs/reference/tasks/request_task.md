RequestTask
===============

Call a Rest Request and get result.

Task reference
--------------

* **Client Service Interface**: `CleverAge\RestProcessBundle\Client\ClientInterface`
* **Task Service**: `CleverAge\RestProcessBundle\Task\RequestTask`

Accepted inputs
---------------

`array`: inputs are merged with task defined options.

Possible outputs
----------------

`string`: the result content of the rest call.

Options
-------

### For Client

| Code   | Type     | Required | Default | Description                                    |
|--------|----------|:--------:|---------|------------------------------------------------|
| `code` | `string` |  **X**   |         | Service identifier, used by Task client option |
| `uri`  | `string` |  **X**   |         | Base uri, concatenated with Task `url`         |

### For Task

| Code                  | Type                        | Required | Default            | Description                                                                              |
|-----------------------|-----------------------------|:--------:|--------------------|------------------------------------------------------------------------------------------|
| `client`              | `string`                    |  **X**   |                    | `ClientInterface` service identifier                                                     |
| `url`                 | `string`                    |  **X**   |                    | Relative url to call                                                                     |
| `method`              | `string`                    |  **X**   |                    | HTTP method from `['HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'TRACE', 'PATCH']` |
| `headers`             | `array`                     |          | `[]`               |                                                                                          |
| `url_parameters`      | `array`                     |          | `[]`               | Search/Replace data on `url`                                                             |
| `data`                | `array`, `string` or `null` |          | `null`             | Treated as `body`, `query` or `json` on HttpClient, depending on `method` and `sends`    |
| `sends`               | `string`                    |          | `application/json` | `Content-Type` header, if value is not empty                                             |
| `expects`             | `string`                    |          | `application/json` | `Accept` header, if value is not empty                                                   |
| `valid_response_code` | `array`                     |          | `[200]`            | One or more [HTTP status code](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)  |
| `log_response`        | `bool`                      |          | `false`            |                                                                                          |

Examples
--------

### Client

```yaml
services:
  app.cleverage_rest_process.client.apicarto_ign:
    class: CleverAge\RestProcessBundle\Client\Client
    bind:
      $code: 'domain_sample'
      $uri: 'https://domain/api'
    tags:
      - { name: cleverage.rest.client }
```  

### Task

```yaml
# Task configuration level
code:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  error_strategy: 'stop'
  options:
    client: domain_sample
    url: '/sample/{parameter}'
    method: 'GET'
    url_parameters: { parameter: '{{ parameter }}' }
```

```yaml
# Task configuration level
code:
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
  error_strategy: 'stop'
  options:
    client: domain_sample
    url: '/sample'
    method: 'POST'
    data: # May be a json string or an array
      parameter_1:
        parameter_11: "eleven"
        array: [-1, 666]
```
