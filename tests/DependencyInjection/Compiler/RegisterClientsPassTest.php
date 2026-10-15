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

namespace CleverAge\RestProcessBundle\Tests\DependencyInjection\Compiler;

use CleverAge\RestProcessBundle\Client\Client;
use CleverAge\RestProcessBundle\DependencyInjection\Compiler\RegisterClientsPass;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\MockHttpClient;

#[CoversClass(RegisterClientsPass::class)]
#[UsesClass(ClientRegistry::class)]
#[UsesClass(Client::class)]
class RegisterClientsPassTest extends TestCase
{
    public function testClientsWithACodeAreRegisteredInALocator(): void
    {
        $container = $this->createContainer(['app.client' => 'client', 'app.other' => 'other'], withCode: true);
        $container->compile(true);

        /** @var ClientRegistry $registry */
        $registry = $container->get('cleverage_rest_process.registry.client');
        // Not instantiated with the registry
        self::assertFalse($container->initialized('app.client'));
        self::assertSame('client', $registry->getClient('client')->getCode());
        self::assertTrue($container->initialized('app.client'));
        self::assertFalse($container->initialized('app.other'));
        self::assertSame('other', $registry->getClient('other')->getCode());
    }

    public function testClientsWithoutCodeAreAddedToTheRegistry(): void
    {
        $container = $this->createContainer(['app.client' => 'client', 'app.other' => 'other']);
        $container->compile(true);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            /** @var ClientRegistry $registry */
            $registry = $container->get('cleverage_rest_process.registry.client');
        } finally {
            restore_error_handler();
        }

        self::assertCount(2, $deprecations);
        self::assertSame('client', $registry->getClient('client')->getCode());
        self::assertSame('other', $registry->getClient('other')->getCode());
    }

    public function testDuplicateCodeFailsTheCompilation(): void
    {
        $container = $this->createContainer(['app.client' => 'client', 'app.client_duplicate' => 'client'], withCode: true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The REST client code "client" is used by the "app.client" and "app.client_duplicate" services.');
        $container->compile(true);
    }

    public function testEmptyCodeFailsTheCompilation(): void
    {
        $container = $this->createContainer([]);
        $container->setDefinition('app.client', new Definition(Client::class))
            ->addTag('cleverage.rest.client', ['code' => '']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "code" attribute of the "cleverage.rest.client" tag of the "app.client" service must be a non-empty string.');
        $container->compile(true);
    }

    public function testWithoutRegistry(): void
    {
        $container = new ContainerBuilder();
        (new RegisterClientsPass())->process($container);

        self::assertFalse($container->has('cleverage_rest_process.registry.client'));
    }

    /**
     * @param array<string, string> $clients Codes of the clients, indexed by service id
     */
    private function createContainer(array $clients, bool $withCode = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new RegisterClientsPass());
        $container->setDefinition('cleverage_rest_process.registry.client', new Definition(ClientRegistry::class, [null]))
            ->setPublic(true);
        foreach ($clients as $id => $code) {
            $container->setDefinition($id, new Definition(Client::class, [new Definition(MockHttpClient::class), new Definition(NullLogger::class), $code, 'https://example.com']))
                ->setPublic(true)
                ->addTag('cleverage.rest.client', $withCode ? ['code' => $code] : []);
        }

        return $container;
    }
}
