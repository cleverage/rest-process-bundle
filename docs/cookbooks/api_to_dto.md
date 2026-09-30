Fetch API data into DTOs
========================

This recipe calls a REST API with a parameter given on execution, deserializes the JSON response into a list of DTOs,
then exports them to a CSV file.

It uses the public [API Carto](https://apicarto.ign.fr/api/doc/codes-postaux) of the IGN, which lists the French
municipalities of a postal code, e.g. `GET https://apicarto.ign.fr/api/codes-postaux/communes/46800`:

```text
[
  {"codePostal": "46800", "codeCommune": "...", "nomCommune": "...", "libelleAcheminement": "..."},
  ...
]
```

The DTO:

```php
<?php

declare(strict_types=1);

namespace App\Dto;

class Commune
{
    public string $codePostal;
    public string $codeCommune;
    public string $nomCommune;
    public string $libelleAcheminement;
}
```

The REST client (with autowiring enabled, see [REST client](../reference/client.md)):

```yaml
# config/services.yaml
services:
  app.cleverage_rest_process.client.apicarto_ign:
    class: CleverAge\RestProcessBundle\Client\Client
    bind:
      $code: 'apicarto_ign'
      $uri: 'https://apicarto.ign.fr/api'
    tags:
      - { name: cleverage.rest.client }
```

The process, executed with `bin/console cleverage:process:execute app.communes_export -c codePostal:"'46800'"`:

```yaml
clever_age_process:
    configurations:
        app.communes_export:
            description: 'Export the municipalities of a postal code'
            help: "bin/console cleverage:process:execute app.communes_export -c codePostal:\"'46800'\""
            tasks:
                fetch:
                    service: '@CleverAge\RestProcessBundle\Task\RequestTask'
                    error_strategy: stop
                    options:
                        client: apicarto_ign
                        url: '/codes-postaux/communes/{codePostal}'
                        method: GET
                        url_parameters: { codePostal: '{{ codePostal }}' }
                    outputs: [deserialize]

                deserialize:
                    service: '@CleverAge\ProcessBundle\Task\Serialization\DeserializerTask'
                    options:
                        type: 'App\Dto\Commune[]'
                        format: json
                    outputs: [iterate]

                iterate:
                    service: '@CleverAge\ProcessBundle\Task\InputIteratorTask'
                    outputs: [normalize]

                normalize:
                    service: '@CleverAge\ProcessBundle\Task\Serialization\NormalizerTask'
                    options:
                        format: csv
                    outputs: [write]

                write:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/exports/communes_{date}.csv'
```

How it works:
- The [RequestTask](../reference/tasks/request_task.md) replaces `{codePostal}` in the URL by the `codePostal` context
  value, then sends `GET https://apicarto.ign.fr/api/codes-postaux/communes/46800` through the `apicarto_ign`
  [client](../reference/client.md). It outputs the raw JSON body as a string. With `error_strategy: stop`, an invalid
  status code (e.g. `404` for an unknown postal code) stops the process.
- The [DeserializerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/deserializer_task.md)
  decodes the JSON and denormalizes it into an array of `App\Dto\Commune` (the `[]` suffix denormalizes a list).
- The [InputIteratorTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/input_iterator_task.md)
  outputs each DTO one by one.
- The [NormalizerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/normalizer_task.md)
  converts each DTO back into an array, written as a line by the
  [CsvWriterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/csv_writer_task.md) (the
  headers are the keys of the first line).

To keep the decoded data as arrays instead of DTOs, replace the `deserialize` task by a
[TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md) using
the [callback](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/callback_transformer.md)
transformer:

```yaml
# Task configuration level
json_decode:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      callback:
        callback: json_decode
        right_parameters: [true] # Decode JSON objects as associative arrays
  outputs: [iterate]
```

To send a JSON payload instead, use the `POST` method with `data` (see the
[RequestTask examples](../reference/tasks/request_task.md#examples)).
