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

/**
 * Holds all tagged rest client services.
 */
class ClientRegistry
{
    /** @var ClientInterface[] */
    private array $clients = [];

    /** @var array<string, string|null> Service ids of the clients, indexed by code */
    private array $serviceIds = [];

    /**
     * @param string|null $serviceId Id of the client service, used to identify the clients with the same code
     */
    public function addClient(ClientInterface $client, ?string $serviceId = null): void
    {
        $code = $client->getCode();
        if (\array_key_exists($code, $this->getClients())) {
            $message = "Client {$code} is already defined";
            if (null !== $this->serviceIds[$code] && null !== $serviceId) {
                $message .= " by service \"{$this->serviceIds[$code]}\", cannot register service \"{$serviceId}\"";
            }

            throw new \UnexpectedValueException($message);
        }
        $this->clients[$code] = $client;
        $this->serviceIds[$code] = $serviceId;
    }

    /**
     * @return ClientInterface[]
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * @throws MissingClientException
     */
    public function getClient(string $code): ClientInterface
    {
        if (!$this->hasClient($code)) {
            throw MissingClientException::create($code);
        }

        return $this->getClients()[$code];
    }

    public function hasClient(string $code): bool
    {
        return \array_key_exists($code, $this->getClients());
    }
}
