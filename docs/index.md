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

The bundle has no configuration of its own. Each API is accessed through a [REST client](reference/client.md): a
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
  service: '@CleverAge\RestProcessBundle\Task\RequestTask'
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
