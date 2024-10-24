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
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RequestTask.
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class RequestTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ClientRegistry */
    protected $registry;

    public function __construct(LoggerInterface $logger, ClientRegistry $registry)
    {
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * @throws MissingClientException
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);

        $requestOptions = $this->getRequestOptions($state);

        $this->logger->debug(
            "Sending request {$requestOptions['method']} to '{$requestOptions['url']}'",
            ['requestOptions' => $requestOptions]
        );
        $result = $this->registry->getClient($options['client'])->call($requestOptions);
        if ($options['log_response']) {
            $this->logger->debug(
                "Response received from '{$options['url']}'",
                [
                    'requestOptions' => $requestOptions,
                    'result' => $result,
                ]
            );
        }

        // Handle empty results
        if (!\in_array($result->code, $options['valid_response_code'], false)) {
            $this->logger->error(
                'REST request failed',
                [
                    'client' => $options['client'],
                    'options' => $options,
                    'raw_headers' => $result->raw_headers,
                    'raw_body' => $result->raw_body,
                ]
            );
            $state->setErrorOutput($result->body);

            if (TaskConfiguration::STRATEGY_SKIP === $state->getTaskConfiguration()->getErrorStrategy()) {
                $state->setSkipped(true);
            } elseif (TaskConfiguration::STRATEGY_STOP === $state->getTaskConfiguration()->getErrorStrategy()) {
                $state->setStopped(true);
            }

            return;
        }

        $state->setOutput($result->body);
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
                'query_parameters' => [],
                'sends' => 'json',
                'expects' => 'json',
                'valid_response_code' => [200],
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
     * @throws ExceptionInterface
     */
    protected function getRequestOptions(ProcessState $state): array
    {
        $options = $this->getOptions($state);

        $requestOptions = [
            'method' => $options['method'],
            'url' => $options['url'],
            'headers' => $options['headers'],
            'url_parameters' => $options['url_parameters'],
            'query_parameters' => $options['query_parameters'],
            'sends' => $options['sends'],
            'expects' => $options['expects'],
        ];

        $input = $state->getInput() ?: [];

        return array_merge($requestOptions, $input);
    }
}
