Push CSV lines to an API
========================

This recipe reads a CSV file and updates one API resource per line, with a `PUT` request whose URL and JSON payload
are built from the line. A line rejected by the API is logged and skipped, without stopping the process.

The CSV file `var/data/products.csv`:

```csv
sku;name;price
AB-001;Blue chair;49.90
AB-002;Red table;129.00
```

The REST client (with autowiring enabled, see [REST client](../reference/client.md)):

```yaml
# config/services.yaml
services:
  app.cleverage_rest_process.client.catalog:
    class: CleverAge\RestProcessBundle\Client\Client
    bind:
      $code: 'catalog'
      $uri: '%env(CATALOG_API_URI)%' # e.g. https://catalog.example.com/api
    tags:
      - { name: cleverage.rest.client }
```

The process:

```yaml
clever_age_process:
    configurations:
        app.products_push:
            description: 'Push the products CSV file to the catalog API'
            tasks:
                read:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/var/data/products.csv'
                    outputs: [build_request]

                build_request:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    url_parameters:
                                        code:
                                            sku: '[sku]'
                                    data:
                                        code: '.'
                                        transformers:
                                            mapping:
                                                mapping:
                                                    name:
                                                        code: '[name]'
                                                    price:
                                                        code: '[price]'
                                                        transformers:
                                                            cast:
                                                                type: float
                    outputs: [push]

                push:
                    service: '@CleverAge\RestProcessBundle\Task\RequestTask'
                    error_strategy: skip # A rejected line does not stop the process
                    options:
                        client: catalog
                        url: '/products/{sku}'
                        method: PUT
                        headers:
                            Authorization: 'Bearer %env(CATALOG_API_TOKEN)%'
                    outputs: [count_pushed]
                    error_outputs: [log_rejected]

                count_pushed:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'

                log_rejected:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
                    options:
                        level: warning
                        message: 'Product rejected by the catalog API'
```

How it works:
- [CsvReaderTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/csv_reader_task.md) is
  iterable: each line goes through the following tasks before the next one is read.
- The [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  builds the request options of the line with the
  [mapping](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/mapping_transformer.md)
  transformer, e.g. `{url_parameters: {sku: 'AB-001'}, data: {name: 'Blue chair', price: 49.9}}`.
- The [RequestTask](../reference/tasks/request_task.md) merges this input with its options: it sends
  `PUT <CATALOG_API_URI>/products/AB-001` with the JSON body `{"name": "Blue chair", "price": 49.9}` (JSON because the
  method is `PUT` and `sends` defaults to `application/json`).
- On success (`200`, `201` or `204`, see `valid_response_code`), the response body goes to the
  [StatCounterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/stat_counter_task.md),
  which logs the number of pushed lines at the end of the process.
- On any other status code, the raw response body is sent to the `error_outputs`: the
  [LoggerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/logger_task.md) logs it as a
  warning, and `error_strategy: skip` goes on with the next line.

Note that the input replaces whole options: an input `headers` key would replace the `Authorization` header configured
on the task. To send the same header to every request of an API, prefer a
[custom client or a scoped HTTP client](../reference/client.md#examples).
