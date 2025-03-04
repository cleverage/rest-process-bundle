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

    public function addClient(ClientInterface $client): void
    {
        if (\array_key_exists($client->getCode(), $this->getClients())) {
            throw new \UnexpectedValueException("Client {$client->getCode()} is already defined");
        }
        $this->clients[$client->getCode()] = $client;
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
