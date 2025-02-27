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
