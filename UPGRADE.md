Upgrade Guide
=============

## v3.3

Unless stated otherwise, the deprecations below trigger a notice (`Since cleverage/rest-process-bundle 3.3: ...`) and
will be removed in 4.0.

### Process bundle 5.4

`cleverage/process-bundle` `^5.4` is required: follow its
[upgrade guide](https://github.com/cleverage/process-bundle/blob/main/UPGRADE.md#v54).

### Configuration key

The configuration key of the bundle is `cleverage_rest_process`: `clever_age_rest_process` is no longer recognized.
The bundle has no configuration, so remove the key: configuring one under `cleverage_rest_process` is deprecated (the
keys were ignored), it will be rejected in 4.0.

### Task code and service id

Reference the task by its code instead of its service:

```yaml
tasks:
    request:
        # Before (deprecated)
        service: '@CleverAge\RestProcessBundle\Task\RequestTask'
        # After
        task: rest_request
```

The service id of the task is its class name: the former id `cleverage_rest_process.task.request` is a deprecated
alias. Fetching the task from the service container (`$container->get()`) is deprecated: it will be private in 4.0.

### Client codes

Registering a client without code is deprecated: declare its code (the one returned by `getCode()`) with the
`#[AsRestClient(code: 'my_client')]` attribute or the `code` attribute of the `cleverage.rest.client` tag, so that it
is only instantiated when used. `ClientRegistry::addClient()` is deprecated.

```yaml
# config/services.yaml
App\Rest\MyClient:
    tags: [{ name: cleverage.rest.client, code: my_client }]
```

### Final classes

`RequestTask`, `ClientRegistry` and `CleverAgeRestProcessBundle` are marked `@final`, they will be `final` in 4.0
(extending them triggers a deprecation with the Symfony `DebugClassLoader`). `Client` and the exceptions stay open.
`CleverAgeRestProcessExtension` and `RegisterClientsPass` are `@internal`.

### Exceptions

The bundle throws exceptions implementing `ProcessExceptionInterface` of the process bundle, extending the exceptions
thrown before: existing `catch` blocks keep working. `RequestTask` throws a `RestRequestException` (instead of an
`\Exception`) for an invalid response code.

## v3.2

### Client URI

`ClientInterface::geUri()` and `Client::geUri()` are deprecated (typo), use `getUri()`: the notice is triggered since
3.3. Implement `getUri()` in your clients not extending `Client`, it will replace `geUri()` in `ClientInterface` in 4.0.
