## Prerequisite

CleverAge/ProcessBundle must be [installed](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#installation).

## Installation

Make sure Composer is installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and install it using composer:

```bash
composer require cleverage/rest-process-bundle
```

Remember to add the following line to `config/bundles.php` (not required if Symfony Flex is used):

```php
CleverAge\RestProcessBundle\CleverAgeRestProcessBundle::class => ['all' => true],
```

## Configuration

The bundle has no configuration of its own. Its task is registered as a service (non-shared) and is used in your
processes by its code, `rest_request` (`task: rest_request`, requires `cleverage/process-bundle` 5.4 or later, see its
[task definition](https://github.com/cleverage/process-bundle/blob/main/docs/reference/02-task_definition.md)).
Referencing it by its class name (`service: '@CleverAge\RestProcessBundle\Task\RequestTask'`) is deprecated since
`cleverage/process-bundle` 5.4.

Its service id is its class name. Fetching it directly from the service container is deprecated since 3.3 (it will be
private in 4.0); its former id, `cleverage_rest_process.task.request`, is a deprecated alias since 3.3, removed in 4.0.

Each API is accessed through a [REST client](reference/client.md): a
service tagged `cleverage.rest.client`, identified by a unique code and holding the base URI of the API.

```yaml
# config/services.yaml
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

The [RequestTask](reference/tasks/request_task.md) then references this client by its code:

```yaml
# Task configuration level
fetch:
  task: rest_request
  options:
    client: apicarto_ign
    url: '/codes-postaux/communes/{codePostal}'
    method: GET
    url_parameters: { codePostal: '{{ codePostal }}' }
```

## Documentation

- Cookbooks
    - [Fetch API data into DTOs](cookbooks/api_to_dto.md)
    - [Push CSV lines to an API](cookbooks/csv_to_api.md)
- Reference
    - [REST client](reference/client.md)
    - Tasks
        - [RequestTask](reference/tasks/request_task.md)
- [CleverAge/ProcessBundle documentation](https://github.com/cleverage/process-bundle/blob/main/docs/index.md)
