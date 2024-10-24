v2.0
------

## BC breaks

* [#3](https://github.com/cleverage/rest-process-bundle/issues/3) Replace `nategood/httpful` dependency by `symfony/http-client`

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
