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

namespace CleverAge\RestProcessBundle\Tests;

use CleverAge\RestProcessBundle\CleverAgeRestProcessBundle;
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

#[CoversClass(CleverAgeRestProcessBundle::class)]
#[UsesClass(ClientRegistry::class)]
#[UsesClass(Client::class)]
#[UsesClass(RegisterClientsPass::class)]
class CleverAgeRestProcessBundleTest extends TestCase
{
    public function testPathIsTheBundleRoot(): void
    {
        $path = (new CleverAgeRestProcessBundle())->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path.'/config/services');
    }

    public function testTaggedClientsAreRegistered(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeRestProcessBundle())->build($container);
        $container->setDefinition('cleverage_rest_process.registry.client', new Definition(ClientRegistry::class))
            ->setPublic(true);
        $container->setDefinition('app.client', new Definition(Client::class, [
            new Definition(MockHttpClient::class),
            new Definition(NullLogger::class),
            'api',
            'https://example.com/api',
        ]))->addTag('cleverage.rest.client');
        $container->compile(true);

        /** @var ClientRegistry $registry */
        $registry = $container->get('cleverage_rest_process.registry.client');
        self::assertSame('api', $registry->getClient('api')->getCode());
    }
}
