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

namespace CleverAge\RestProcessBundle\Registry;

use CleverAge\RestProcessBundle\Client\ClientInterface;
use CleverAge\RestProcessBundle\Exception\MissingClientException;
use CleverAge\RestProcessBundle\Exception\UnexpectedValueException;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Holds the REST clients, indexed by code.
 *
 * The client services are fetched lazily from a service locator indexed by code (`code` attribute of the
 * `cleverage.rest.client` tag, or `#[AsRestClient]`, see RegisterClientsPass): a client is only instantiated when it
 * is used.
 *
 * @final since 3.3
 */
class ClientRegistry
{
    /**
     * Clients already fetched from the locator, and the ones registered with addClient() (deprecated).
     *
     * @var array<string, ClientInterface>
     */
    private array $clients = [];

    /** @var array<string, string|null> Service ids of the clients registered with addClient(), indexed by code */
    private array $serviceIds = [];

    /**
     * @param ServiceProviderInterface<mixed>|null $locator Client services, checked when fetched
     */
    public function __construct(
        private readonly ?ServiceProviderInterface $locator = null,
    ) {
    }

    /**
     * @param string|null $serviceId Id of the client service, used to identify the clients with the same code
     *
     * @deprecated since 3.3, tag the client service with a "code" attribute or use #[AsRestClient] instead
     */
    public function addClient(ClientInterface $client, ?string $serviceId = null): void
    {
        $code = $client->getCode();
        trigger_deprecation(
            'cleverage/rest-process-bundle',
            '3.3',
            'Registering the REST client "%s" (%s) with "%s()" is deprecated, tag its service with a "code" attribute ({ name: cleverage.rest.client, code: %s }) or use the #[AsRestClient] attribute instead, so that it is only instantiated when used. It will be required in 4.0.',
            $code,
            $client::class,
            __METHOD__,
            $code,
        );
        if ($this->hasClient($code)) {
            $message = "Client {$code} is already defined";
            $definedBy = $this->serviceIds[$code] ?? null;
            if (null !== $definedBy && null !== $serviceId) {
                $message .= " by service \"{$definedBy}\", cannot register service \"{$serviceId}\"";
            }

            throw new UnexpectedValueException($message);
        }
        $this->clients[$code] = $client;
        $this->serviceIds[$code] = $serviceId;
    }

    /**
     * Instantiates every client of the locator.
     *
     * @return array<string, ClientInterface>
     */
    public function getClients(): array
    {
        foreach (array_keys($this->locator?->getProvidedServices() ?? []) as $code) {
            $this->getClient((string) $code);
        }

        return $this->clients;
    }

    /**
     * @throws MissingClientException
     */
    public function getClient(string $code): ClientInterface
    {
        if (isset($this->clients[$code])) {
            return $this->clients[$code];
        }
        if (!$this->locator?->has($code)) {
            throw MissingClientException::create($code);
        }

        $client = $this->locator->get($code);
        if (!$client instanceof ClientInterface) {
            throw new UnexpectedValueException(\sprintf('The service of the REST client "%s" must implement "%s".', $code, ClientInterface::class));
        }
        if ($code !== $client->getCode()) {
            throw new UnexpectedValueException(\sprintf('The REST client "%s" is registered with the code "%s" but its getCode() method returns "%s": both must be the same.', $client::class, $code, $client->getCode()));
        }

        return $this->clients[$code] = $client;
    }

    public function hasClient(string $code): bool
    {
        return isset($this->clients[$code]) || ($this->locator?->has($code) ?? false);
    }
}
