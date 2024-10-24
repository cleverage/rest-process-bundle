<?php declare(strict_types=1);
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\RestProcessBundle\Registry;

use CleverAge\RestProcessBundle\Client\ClientInterface;
use CleverAge\RestProcessBundle\Exception\MissingClientException;

/**
 * Holds all tagged rest client services
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class ClientRegistry
{
    /** @var ClientInterface[] */
    private $clients = [];

    /**
     * @param ClientInterface $client
     */
    public function addClient(ClientInterface $client): void
    {
        if (array_key_exists($client->getCode(), $this->getClients())) {
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
     * @param string $code
     *
     * @throws MissingClientException
     *
     * @return ClientInterface
     */
    public function getClient($code): ClientInterface
    {
        if (!$this->hasClient($code)) {
            throw MissingClientException::create($code);
        }

        return $this->getClients()[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasClient($code): bool
    {
        return array_key_exists($code, $this->getClients());
    }
}
