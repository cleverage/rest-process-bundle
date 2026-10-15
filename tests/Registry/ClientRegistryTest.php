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
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(ClientRegistry::class)]
#[UsesClass(MissingClientException::class)]
class ClientRegistryTest extends TestCase
{
    /** @var list<string> Codes of the clients instantiated by the locator */
    private array $instantiated = [];

    public function testClientsAreFetchedLazilyFromTheLocator(): void
    {
        $registry = new ClientRegistry($this->createLocator(['client' => 'client', 'other' => 'other']));

        self::assertTrue($registry->hasClient('client'));
        self::assertFalse($registry->hasClient('missing'));
        self::assertSame([], $this->instantiated);

        $client = $registry->getClient('client');
        self::assertSame('client', $client->getCode());
        self::assertSame($client, $registry->getClient('client'));
        self::assertSame(['client'], $this->instantiated);

        self::assertSame(['client', 'other'], array_keys($registry->getClients()));
        self::assertSame(['client', 'other'], $this->instantiated);
    }

    public function testMissingClient(): void
    {
        $this->expectException(MissingClientException::class);
        $this->expectExceptionMessage('No rest client with code : missing');
        (new ClientRegistry($this->createLocator([])))->getClient('missing');
    }

    public function testMissingClientWithoutLocator(): void
    {
        $this->expectException(MissingClientException::class);
        (new ClientRegistry())->getClient('missing');
    }

    public function testCodeOfTheTagMustBeTheCodeOfTheClient(): void
    {
        $registry = new ClientRegistry($this->createLocator(['client' => 'other']));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/is registered with the code "client" but its getCode\(\) method returns "other"/');
        $registry->getClient('client');
    }

    public function testServiceMustBeAClient(): void
    {
        $registry = new ClientRegistry(new ServiceLocator(['client' => static fn (): \stdClass => new \stdClass()]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The service of the REST client "client" must implement "'.ClientInterface::class.'".');
        $registry->getClient('client');
    }

    public function testAddClientIsDeprecated(): void
    {
        $registry = new ClientRegistry($this->createLocator(['client' => 'client']));
        $other = $this->createClient('other');

        $deprecations = $this->collectDeprecations(static fn () => $registry->addClient($other, 'app.other'));

        self::assertSame(
            ['Since cleverage/rest-process-bundle 3.3: Registering the REST client "other" ('.$other::class.') with "'.ClientRegistry::class.'::addClient()" is deprecated, tag its service with a "code" attribute ({ name: cleverage.rest.client, code: other }) or use the #[AsRestClient] attribute instead, so that it is only instantiated when used. It will be required in 4.0.'],
            $deprecations
        );
        self::assertSame($other, $registry->getClient('other'));
        self::assertEqualsCanonicalizing(['client', 'other'], array_keys($registry->getClients()));
    }

    public function testAddClientWithTheCodeOfALocatorClient(): void
    {
        $registry = new ClientRegistry($this->createLocator(['client' => 'client']));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Client client is already defined$/');
        $this->collectDeprecations(fn () => $registry->addClient($this->createClient('client'), 'app.client_duplicate'));
    }

    public function testDuplicateCodeGivesTheServiceIds(): void
    {
        $registry = new ClientRegistry();
        $this->collectDeprecations(fn () => $registry->addClient($this->createClient('client'), 'app.client'));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Client client is already defined by service "app.client", cannot register service "app.client_duplicate"');
        $this->collectDeprecations(fn () => $registry->addClient($this->createClient('client'), 'app.client_duplicate'));
    }

    public function testDuplicateCodeWithoutServiceIds(): void
    {
        $registry = new ClientRegistry();
        $this->collectDeprecations(fn () => $registry->addClient($this->createClient('client')));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Client client is already defined$/');
        $this->collectDeprecations(fn () => $registry->addClient($this->createClient('client'), 'app.client_duplicate'));
    }

    /**
     * @param array<string, string> $codes Code returned by each client, indexed by the code of the locator
     *
     * @return ServiceLocator<mixed>
     */
    private function createLocator(array $codes): ServiceLocator
    {
        $factories = [];
        foreach ($codes as $code => $clientCode) {
            $factories[$code] = function () use ($code, $clientCode): ClientInterface {
                $this->instantiated[] = $code;

                return $this->createClient($clientCode);
            };
        }

        return new ServiceLocator($factories);
    }

    private function createClient(string $code): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('getCode')->willReturn($code);

        return $client;
    }

    /**
     * @return list<string>
     */
    private function collectDeprecations(callable $callable): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
