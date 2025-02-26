<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/RestProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\RestProcessBundle\Client;

use CleverAge\RestProcessBundle\Exception\RestRequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @phpstan-import-type RequestOptions from \CleverAge\RestProcessBundle\Task\RequestTask
 */
class Client implements ClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $code,
        private string $uri,
    ) {
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function geUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * @param RequestOptions $options
     *
     * @throws RestRequestException
     */
    public function call(array $options = []): ResponseInterface
    {
        $options = $this->getOptions($options);

        if (!\in_array(
            $options['method'],
            ['HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'TRACE', 'PATCH'],
            true
        )) {
            throw new RestRequestException(\sprintf('%s is not an HTTP method', $options['method']));
        }

        try {
            return $this->httpClient->request(
                $options['method'],
                $this->getRequestUri($options),
                $this->getRequestOptions($options),
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Rest request failed',
                [
                    'url' => $this->getRequestUri($options),
                    'error' => $e->getMessage(),
                ]
            );
            throw new RestRequestException('Rest request failed', 0, $e);
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'url',
            ]
        );

        $resolver->setDefaults(
            [
                'method' => 'GET',
                'sends' => 'application/json',
                'expects' => 'application/json',
                'url_parameters' => [],
                'headers' => [],
                'data' => null,
            ]
        );

        $resolver->setAllowedTypes('url', ['string']);
        $resolver->setAllowedTypes('method', ['string']);
        $resolver->setAllowedTypes('sends', ['string']);
        $resolver->setAllowedTypes('expects', ['string']);
        $resolver->setAllowedTypes('url_parameters', ['array']);
        $resolver->setAllowedTypes('headers', ['array']);
        $resolver->setAllowedTypes('data', ['array', 'string', 'null']);
    }

    /**
     * @param RequestOptions $options
     *
     * @return RequestOptions
     */
    protected function getOptions(array $options = []): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        /** @var RequestOptions $resolved */
        $resolved = $resolver->resolve($options);

        return $resolved;
    }

    /**
     * @param RequestOptions $options
     */
    protected function getRequestUri(array $options = []): string
    {
        return $this->replaceParametersInUri($this->constructUri($options), $options);
    }

    /**
     * @param RequestOptions $options
     *
     * @return array{
     *     'headers': array<mixed>,
     *     'json'?: array<mixed>|string|null,
     *     'query'?: array<mixed>|string|null,
     *     'body'?: array<mixed>|string|null
     * }
     */
    protected function getRequestOptions(array $options = []): array
    {
        $requestOptions = [];
        $requestOptions['headers'] = empty($options['headers']) ? [] : $options['headers'];
        if (!empty($options['sends'])) {
            $requestOptions['headers']['Content-Type'] = $options['sends'];
        }
        if (!empty($options['expects'])) {
            $requestOptions['headers']['Accept'] = $options['expects'];
        }
        if (\in_array($options['method'], ['POST', 'PATCH', 'PUT']) && 'application/json' === $options['sends']) {
            $requestOptions['json'] = $options['data'];
        } elseif ('GET' === $options['method']) {
            $requestOptions['query'] = $options['data'];
        } else {
            $requestOptions['body'] = $options['data'];
        }

        return $requestOptions;
    }

    /**
     * @param RequestOptions $options
     */
    protected function constructUri(array $options): string
    {
        $uri = ltrim((string) $options['url'], '/');

        return \sprintf('%s/%s', $this->getApiUrl(), $uri);
    }

    protected function getApiUrl(): string
    {
        return $this->geUri();
    }

    /**
     * @param RequestOptions $options
     */
    protected function replaceParametersInUri(string $uri, array $options = []): string
    {
        if ($options['url_parameters']) {
            /** @var array<string> $search */
            $search = array_keys($options['url_parameters']);
            array_walk(
                $search,
                static function (&$item, $key) {
                    $item = '{'.$item.'}';
                }
            );
            /** @var array<string> $replace */
            $replace = array_values($options['url_parameters']);
            array_walk(
                $replace,
                static function (&$item, $key) {
                    $item = rawurlencode($item);
                }
            );

            $uri = str_replace($search, $replace, $uri);
        }

        return $uri;
    }
}
