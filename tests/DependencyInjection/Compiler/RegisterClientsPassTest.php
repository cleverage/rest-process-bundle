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
use Symfony\Component\HttpClient\MockHttpClient;

#[CoversClass(RegisterClientsPass::class)]
#[UsesClass(ClientRegistry::class)]
#[UsesClass(Client::class)]
class RegisterClientsPassTest extends TestCase
{
    public function testClientsAreRegistered(): void
    {
        $container = $this->createContainer(['app.client' => 'client', 'app.other' => 'other']);
        $container->compile(true);

        /** @var ClientRegistry $registry */
        $registry = $container->get('cleverage_rest_process.registry.client');
        self::assertSame('client', $registry->getClient('client')->getCode());
        self::assertSame('other', $registry->getClient('other')->getCode());
    }

    public function testDuplicateCodeGivesTheServiceIds(): void
    {
        $container = $this->createContainer(['app.client' => 'client', 'app.client_duplicate' => 'client']);
        $container->compile(true);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Client client is already defined by service "app.client", cannot register service "app.client_duplicate"');
        $container->get('cleverage_rest_process.registry.client');
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
    private function createContainer(array $clients): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new RegisterClientsPass());
        $container->setDefinition('cleverage_rest_process.registry.client', new Definition(ClientRegistry::class))
            ->setPublic(true);
        foreach ($clients as $id => $code) {
            $container->setDefinition($id, new Definition(Client::class, [new Definition(MockHttpClient::class), new Definition(NullLogger::class), $code, 'https://example.com']))
                ->addTag('cleverage.rest.client');
        }

        return $container;
    }
}
