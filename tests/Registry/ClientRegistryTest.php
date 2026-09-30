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

namespace CleverAge\RestProcessBundle\Tests\Registry;

use CleverAge\RestProcessBundle\Client\ClientInterface;
use CleverAge\RestProcessBundle\Exception\MissingClientException;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientRegistry::class)]
#[UsesClass(MissingClientException::class)]
class ClientRegistryTest extends TestCase
{
    public function testGetClient(): void
    {
        $registry = new ClientRegistry();
        $client = $this->createClient('client');
        $other = $this->createClient('other');
        $registry->addClient($client, 'app.client');
        $registry->addClient($other);

        self::assertSame($client, $registry->getClient('client'));
        self::assertSame($other, $registry->getClient('other'));
        self::assertTrue($registry->hasClient('client'));
        self::assertSame(['client' => $client, 'other' => $other], $registry->getClients());
    }

    public function testMissingClient(): void
    {
        $this->expectException(MissingClientException::class);
        $this->expectExceptionMessage('No rest client with code : missing');
        (new ClientRegistry())->getClient('missing');
    }

    public function testDuplicateCodeGivesTheServiceIds(): void
    {
        $registry = new ClientRegistry();
        $registry->addClient($this->createClient('client'), 'app.client');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Client client is already defined by service "app.client", cannot register service "app.client_duplicate"');
        $registry->addClient($this->createClient('client'), 'app.client_duplicate');
    }

    public function testDuplicateCodeWithoutServiceIds(): void
    {
        $registry = new ClientRegistry();
        $registry->addClient($this->createClient('client'));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Client client is already defined$/');
        $registry->addClient($this->createClient('client'), 'app.client_duplicate');
    }

    private function createClient(string $code): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('getCode')->willReturn($code);

        return $client;
    }
}
