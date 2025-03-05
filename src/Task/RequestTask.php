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

namespace CleverAge\RestProcessBundle\Task;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\RestProcessBundle\Exception\MissingClientException;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @phpstan-type Options array{
 *      'client': string,
 *      'url': string,
 *      'method': string,
 *      'headers': array<mixed>,
 *      'url_parameters': array<mixed>,
 *      'data': array<mixed>|string|null,
 *      'sends': string,
 *      'expects': string,
 *      'valid_response_code': array<int>,
 *      'log_response': bool,
 * }
 * @phpstan-type RequestOptions array{
 *      'url': string,
 *      'method': string,
 *      'headers': array<mixed>,
 *      'url_parameters': array<mixed>,
 *      'sends': string,
 *      'expects': string,
 *      'data': array<mixed>|string|null
 * }
 */
class RequestTask extends AbstractConfigurableTask
{
    public function __construct(protected LoggerInterface $logger, protected ClientRegistry $registry)
    {
    }

    /**
     * @throws MissingClientException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Throwable
     */
    public function execute(ProcessState $state): void
    {
        /** @var Options $options */
        $options = $this->getOptions($state);

        $requestOptions = $this->getRequestOptions($state);

        $this->logger->debug(
            "Sending request {$requestOptions['method']} to '{$requestOptions['url']}'",
            ['requestOptions' => $requestOptions]
        );
        $response = $this->registry->getClient($options['client'])->call($requestOptions);
        if ($options['log_response']) {
            $this->logger->debug(
                "Response received from '{$options['url']}'",
                [
                    'requestOptions' => $requestOptions,
                    'result' => $response,
                ]
            );
        }

        // Handle empty results
        try {
            if (!\in_array($response->getStatusCode(), $options['valid_response_code'], false)) {
                $state->setErrorOutput($response->getContent(false));

                if (TaskConfiguration::STRATEGY_SKIP === $state->getTaskConfiguration()->getErrorStrategy()) {
                    $state->setSkipped(true);
                } elseif (TaskConfiguration::STRATEGY_STOP === $state->getTaskConfiguration()->getErrorStrategy()) {
                    $state->setStopped(true);
                }

                throw new \Exception('Invalid response code');
            }

            $state->setOutput($response->getContent());
        } catch (\Throwable $e) {
            $allowRedirectionException = false;
            $allowClientException = false;
            foreach ($options['valid_response_code'] as $code) {
                if ($code >= 300 && $code < 400) {
                    $allowRedirectionException = true;
                }
                if ($code >= 400 && $code < 500) {
                    $allowClientException = true;
                }
            }
            if (!($allowRedirectionException && $e instanceof RedirectionExceptionInterface)
                && !($allowClientException && $e instanceof ClientExceptionInterface)
            ) {
                $this->logger->error(
                    'REST request failed',
                    [
                        'client' => $options['client'],
                        'options' => $options,
                        'request_options' => $requestOptions,
                        'message' => $e->getMessage(),
                        'raw_headers' => $response->getHeaders(false),
                        'raw_body' => $response->getContent(false),
                    ]
                );
            }

            throw $e;
        }
    }

    /**
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'client',
                'url',
                'method',
            ]
        );
        $resolver->setDefaults(
            [
                'headers' => [],
                'url_parameters' => [],
                'data' => null,
                'sends' => 'application/json',
                'expects' => 'application/json',
                'valid_response_code' => [200, 201, 204],
                'log_response' => false,
            ]
        );

        $resolver->setAllowedTypes('client', ['string']);
        $resolver->setAllowedTypes('url', ['string']);
        $resolver->setAllowedTypes('method', ['string']);
        $resolver->setAllowedTypes('valid_response_code', ['array']);
        $resolver->setAllowedTypes('log_response', ['bool']);
    }

    /**
     * @return RequestOptions
     */
    protected function getRequestOptions(ProcessState $state): array
    {
        /** @var Options $options */
        $options = $this->getOptions($state);

        $requestOptions = [
            'url' => $options['url'],
            'method' => $options['method'],
            'headers' => $options['headers'],
            'url_parameters' => $options['url_parameters'],
            'sends' => $options['sends'],
            'expects' => $options['expects'],
            'data' => $options['data'],
        ];

        /** @var array<mixed> $input */
        $input = $state->getInput() ?: [];

        /** @var RequestOptions $mergedOptions */
        $mergedOptions = array_merge($requestOptions, $input);

        return $mergedOptions;
    }
}
