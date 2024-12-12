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
        } catch (\Exception|\Throwable $e) {
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

    protected function getOptions(array $options = []): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $resolver->resolve($options);
    }

    protected function getRequestUri(array $options = []): string
    {
        return $this->replaceParametersInUri($this->constructUri($options), $options);
    }

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
        if ('POST' === $options['method'] && 'application/json' === $options['sends']) {
            $requestOptions['json'] = $options['data'];
        } elseif ('GET' === $options['method']) {
            $requestOptions['query'] = $options['data'];
        } else {
            $requestOptions['body'] = $options['data'];
        }

        return $requestOptions;
    }

    protected function constructUri(array $options): string
    {
        $uri = ltrim((string) $options['url'], '/');

        return \sprintf('%s/%s', $this->getApiUrl(), $uri);
    }

    protected function getApiUrl(): string
    {
        return $this->geUri();
    }

    protected function replaceParametersInUri(string $uri, array $options = []): string
    {
        if (\array_key_exists('url_parameters', $options) && $options['url_parameters']) {
            $search = array_keys($options['url_parameters']);
            array_walk(
                $search,
                static function (&$item, $key) {
                    $item = '{'.$item.'}';
                }
            );
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
