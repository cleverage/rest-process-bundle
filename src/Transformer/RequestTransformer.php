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

namespace CleverAge\RestProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use CleverAge\RestProcessBundle\Exception\MissingClientException;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestTransformer implements ConfigurableTransformerInterface
{
    public function __construct(protected LoggerInterface $logger, protected ClientRegistry $registry)
    {
    }

    /**
     * @throws MissingClientException
     * @throws TransformerException
     */
    public function transform(mixed $value, array $options = []): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $client = $this->registry->getClient($options['client']);

        $requestOptions = [
            'url' => $options['url'],
            'headers' => $options['headers'],
            'url_parameters' => $options['url_parameters'],
            'query_parameters' => $options['query_parameters'],
            'sends' => $options['sends'],
            'expects' => $options['expects'],
        ];

        $input = $value ?: [];
        $requestOptions = array_merge($requestOptions, $input);
        $response = $client->call($requestOptions);

        // Handle empty results
        try {
            if (!\in_array($response->getStatusCode(), $options['valid_response_code'], false)) {
                throw new \Exception('Invalid response code');
            }

            return $response->getContent(false);
        } catch (\Exception|\Throwable $e) {
            $this->logger->error(
                'REST request failed',
                [
                    'client' => $options['client'],
                    'options' => $options,
                    'message' => $e->getMessage(),
                    'raw_headers' => $response->getHeaders(false),
                    'raw_body' => $response->getContent(false),
                ]
            );

            throw new TransformerException('REST request failed');
        }
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'rest_request';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'client',
                'url',
                'method',
            ]
        );
        $resolver->setDefault('headers', []);
        $resolver->setDefault('url_parameters', []);
        $resolver->setDefault('query_parameters', []);
        $resolver->setDefault('sends', 'json');
        $resolver->setDefault('expects', 'json');
        $resolver->setDefault('valid_response_code', [200]);
        $resolver->setAllowedTypes('client', ['string']);
        $resolver->setAllowedTypes('url', ['string']);
        $resolver->setAllowedTypes('method', ['string']);
        $resolver->setAllowedTypes('valid_response_code', ['array']);
    }
}
