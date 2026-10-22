Latest
------

v3.3.1
------

### Fixes
* [GHSA-fq22-3c2c-cv5p](https://github.com/cleverage/rest-process-bundle/security/advisories/GHSA-fq22-3c2c-cv5p) `RequestTask`: mask the credentials in the logs (request sent, `log_response`, failure): the values of the headers, `data` and `url_parameters` keys whose name contains `authorization`, `cookie`, `password`, `secret`, `token`, `api_key`, `signature`, `credential` or `session`, and the `Set-Cookie` response headers, are replaced by `***`.

v3.3
------

### Changes
* Require `cleverage/process-bundle` `^5.4`.
* Please follow [UPGRADE.md v3.3](UPGRADE.md#v33)
* [#73](https://github.com/cleverage/rest-process-bundle/issues/73) Document that a client, shared, keeps its state between two process executions in a long-running worker: a base URI changed with `setUri()` is not reset.
* [#71](https://github.com/cleverage/rest-process-bundle/issues/71) The configuration key of the bundle (its extension alias) is `cleverage_rest_process`, instead of `clever_age_rest_process` (no option could be configured there). Configuring a key under `cleverage_rest_process` is deprecated: the bundle has no configuration and the keys were ignored without notice (new internal `Configuration`, empty; notice when the container is compiled). They will be rejected in 4.0. Update documentation, add tests.
* [#69](https://github.com/cleverage/rest-process-bundle/issues/69) The bundle throws exceptions implementing `ProcessExceptionInterface` of the process bundle instead of raw SPL exceptions (as [cleverage/process-bundle#317](https://github.com/cleverage/process-bundle/issues/317)): new `Exception\UnexpectedValueException`, extending the SPL exceptions thrown before (same messages, the existing `catch` keep working). `RestException` (and so `RestRequestException` and `MissingClientException`) implements `ProcessExceptionInterface`; `RequestTask` throws a `RestRequestException` instead of a raw `\Exception` for an invalid response code (same message). Add tests.
* [#67](https://github.com/cleverage/rest-process-bundle/issues/67) Composer: require PHP `^8.2` instead of `>=8.2` (PHP 9 is no longer accepted before being tested), remove the authorization of the `symfony/flex` plugin, which is not required (the CI installs it globally), as [cleverage/process-bundle#319](https://github.com/cleverage/process-bundle/issues/319).
* [#62](https://github.com/cleverage/rest-process-bundle/issues/62) Define the public API (Symfony BC promise, as [cleverage/process-bundle#279](https://github.com/cleverage/process-bundle/issues/279)): `RequestTask`, `ClientRegistry` and `CleverAgeRestProcessBundle` are marked `@final since 3.3`, they will be `final` in 4.0: extending them triggers a deprecation with the Symfony `DebugClassLoader`. `Client` (extended by the applications) and the exceptions stay open. `CleverAgeRestProcessExtension` and `RegisterClientsPass` are marked `@internal`.
* [#61](https://github.com/cleverage/rest-process-bundle/issues/61) `CleverAgeRestProcessExtension::findServices()` loads the service definition files with a glob pattern instead of a `Finder` (same files, as [cleverage/process-bundle#271](https://github.com/cleverage/process-bundle/issues/271)), `symfony/finder` is no longer required. Add tests.
* [#58](https://github.com/cleverage/rest-process-bundle/issues/58) ClientRegistry: the clients are loaded lazily from a service locator indexed by code: a client is only instantiated when a task uses it (an error in a client that a process does not use no longer makes it fail). The code is given by the new `code` attribute of the `cleverage.rest.client` tag, or by the new `#[AsRestClient(code: '...')]` attribute (autoconfiguration); it must be the one returned by `getCode()`. Duplicate or empty codes fail the container compilation. A client tagged without `code` is still registered eagerly, with `ClientRegistry::addClient()`, which is deprecated (the `code` will be required in 4.0). New optional `ClientRegistry` constructor argument (the locator); `ClientRegistry` is autowirable by its class name. Require `symfony/service-contracts`. Update documentation, add tests.
* [#56](https://github.com/cleverage/rest-process-bundle/issues/56) Require `symfony/polyfill-php83`, `symfony/polyfill-php84` and `symfony/polyfill-php85` ([cleverage/process-bundle#299](https://github.com/cleverage/process-bundle/issues/299)): the functions of PHP 8.3 to 8.5 can be used while supporting PHP 8.2. Rector targets PHP 8.2 (`withPhpVersion()`, `withPhpSets()` read from `composer.json`) and applies the rules using a newer function when its polyfill is required (no change needed in the current code).
* [#52](https://github.com/cleverage/rest-process-bundle/issues/52) Give a code to the task ([cleverage/process-bundle#91](https://github.com/cleverage/process-bundle/issues/91)): `rest_request` (`task: rest_request`, with `cleverage/process-bundle` 5.4 or later, referencing it by `service` being deprecated there). The task has its class name as service id; fetching it directly from the service container is deprecated (`container.private`), it will be private in 4.0. The former id `cleverage_rest_process.task.request` is a deprecated alias, removed in 4.0. Update documentation, add tests.
* [#50](https://github.com/cleverage/rest-process-bundle/issues/50) CI: test the lowest versions allowed by `composer.json` (`--prefer-lowest`, PHP 8.2). Raise `psr/log` to `^2 || ^3`: `cleverage/process-bundle` loggers and the test loggers use the typed `log()` signature, a fatal error with `psr/log` 1.
* [#48](https://github.com/cleverage/rest-process-bundle/issues/48) `Client::geUri()` (deprecated since v3.2, use `getUri()`) triggers a deprecation at runtime with `trigger_deprecation()` (`symfony/deprecation-contracts`, now required). Add tests.
* [#46](https://github.com/cleverage/rest-process-bundle/issues/46) Add a `.gitattributes`: the development files (`.docker/`, `.github/`, `tests/`, quality tools and PHPUnit configurations, `Makefile`) are no longer part of the archive installed by Composer (`dist`).
* [#44](https://github.com/cleverage/rest-process-bundle/issues/44) Require `psr/log`, used directly by `Client` and `RequestTask` but only installed through `cleverage/process-bundle`.

v3.2
------

### Changes
* [#31](https://github.com/cleverage/rest-process-bundle/issues/31) Add missing tests: RequestTask and Client with a mocked HTTP client, MissingClientException, bundle and DI extension.
* [#39](https://github.com/cleverage/rest-process-bundle/issues/39) Add `Client::getUri()`, deprecate the misspelled `geUri()`; type `MissingClientException::create()`. Update documentation, add tests.
* [#33](https://github.com/cleverage/rest-process-bundle/issues/33) Give the ids of both services in the error on duplicate client codes: the clients are registered by a compiler pass of the bundle, `ClientRegistry::addClient()` gets an optional `$serviceId` argument. Update documentation, add tests.

### Fixes
* [#35](https://github.com/cleverage/rest-process-bundle/issues/35) Fix RequestTask: a `3xx` / `4xx` / `5xx` status code listed in `valid_response_code` outputs the response body (the task still failed). Update documentation, add tests.
* [#36](https://github.com/cleverage/rest-process-bundle/issues/36) Fix RequestTask: log transport errors (the log was lost, reading the response details threw again). Update documentation, add tests.
* [#37](https://github.com/cleverage/rest-process-bundle/issues/37) Fix RequestTask `log_response`: log the requested URL, and the status code, headers and content of the response (instead of the configured URL and the response object). Update documentation, add tests.
* [#38](https://github.com/cleverage/rest-process-bundle/issues/38) Fix RequestTask: throw an explicit `\UnexpectedValueException` on a non-array input; Client: convert scalar URL parameters to strings (a `TypeError` was triggered). Update documentation, add tests.

v3.1
------

### Changes
* [#28](https://github.com/cleverage/rest-process-bundle/issues/28) Update quality stack: use Rector `withComposerBased()` sets (removed `SYMFONY_64` / `PHPUNIT_100` sets), declare used Symfony packages and PHPUnit range in composer.json, apply quality tools fixes
* [#30](https://github.com/cleverage/rest-process-bundle/issues/30) Add missing documentations: REST client reference page, complete RequestTask reference, cookbooks. Harmonize and fix existing documentation.

v3.0
------

### Changes
* [#22](https://github.com/cleverage/rest-process-bundle/issues/22) Add support for PHP 8.5 and Symfony 8.* Update phpunit/phpunit to version >10.0 Bump version to cleverage/process-bundle ^5.0

### BC breaks
* [#22](https://github.com/cleverage/rest-process-bundle/issues/22) Remove support for PHP 8.1 and Symfony 7.3

v2.3
------

### Changes
* [#20](https://github.com/cleverage/rest-process-bundle/issues/20) Upgrade to Symfony 7.3 & PHP 8.4

v2.2
------

### Changes
* [#17](https://github.com/cleverage/rest-process-bundle/issues/17) Improve errors handling using valid_response_code.
  Allow ClientExceptionInterface if valid_response_code between 400 & 499 exists. Allow RedirectionExceptionInterface if valid_response_code between 300 & 399 exists.

### Fixes 
* [#16](https://github.com/cleverage/rest-process-bundle/issues/16) Add missing shared: false on tasks

v2.1
------

### Changes

* [#10](https://github.com/cleverage/rest-process-bundle/issues/10) Add 201 and 204 to valid_response_code
* [#14](https://github.com/cleverage/rest-process-bundle/issues/14) Add request_options on RequestTask error log

### Fixes

* [#12](https://github.com/cleverage/rest-process-bundle/issues/12) PUT and PATCH with send: application/json must be send as json.

v2.0
------

## BC breaks

* [#3](https://github.com/cleverage/rest-process-bundle/issues/3) Replace `nategood/httpful` dependency by `symfony/http-client`
* [#3](https://github.com/cleverage/rest-process-bundle/issues/5) Update Tasks for "symfony/http-client": "^6.4|^7.1"
* [#4](https://github.com/cleverage/rest-process-bundle/issues/4) Update services according to Symfony best practices. 
Services should not use autowiring or autoconfiguration. Instead, all services should be defined explicitly.
Services must be prefixed with the bundle alias instead of using fully qualified class names => `cleverage_rest_process`
* RequestTask : `query_parameters` option is deprecated, use `data` instead
* Remove RequestTransformer, use RequestTask instead.

### Changes

* [#1](https://github.com/cleverage/rest-process-bundle/issues/1) Add Makefile & .docker for local standalone usage
* [#1](https://github.com/cleverage/rest-process-bundle/issues/1) Add rector, phpstan & php-cs-fixer configurations & apply it
* [#2](https://github.com/cleverage/rest-process-bundle/issues/2) Remove `sidus/base-bundle` dependency

### Fixes

v1.0.4
------

### Changes

* Fixed dependencies after removing sidus/base-bundle from the base process bundle

v1.0.3
------

### Changes

* Minor refactoring in RequestTask to allow override of options more easily

v1.0.2
------

### Fixes

* Fixing trailing '?'/'&' in request uri

v1.0.1
------

### Changes

* Adding debug information in RequestTask

v1.0.0
------

* Initial release
