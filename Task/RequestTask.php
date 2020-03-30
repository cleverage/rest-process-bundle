<?php declare(strict_types=1);
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\RestProcessBundle\Task;

use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RequestTask
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class RequestTask extends AbstractConfigurableTask
{

    /** @var LoggerInterface */
    protected $logger;

    /** @var ClientRegistry */
    protected $registry;

    /**
     * RequestTask constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, ClientRegistry $registry)
    {
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     * @param ProcessState $state
     *
     * @throws \CleverAge\RestProcessBundle\Exception\MissingClientException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);

        $client = $this->registry->getClient($options['client']);

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
        $requestOptions = array_merge($requestOptions, $input);
        $this->logger->debug(
            "Sending request {$options['method']} to '{$options['url']}'",
            ['requestOptions' => $requestOptions]
        );
        $result = $client->call($requestOptions);
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

            if ($state->getTaskConfiguration()->getErrorStrategy() === TaskConfiguration::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($state->getTaskConfiguration()->getErrorStrategy() === TaskConfiguration::STRATEGY_STOP) {
                $state->setStopped(true);
            }

            return;
        }

        $state->setOutput($result->body);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
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
}
