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

namespace CleverAge\RestProcessBundle\Tests\DependencyInjection;

use CleverAge\RestProcessBundle\DependencyInjection\CleverAgeRestProcessExtension;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\RestProcessBundle\Task\RequestTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(CleverAgeRestProcessExtension::class)]
class CleverAgeRestProcessExtensionTest extends TestCase
{
    public function testServices(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeRestProcessExtension())->load([], $container);

        self::assertSame(ClientRegistry::class, $container->getDefinition('cleverage_rest_process.registry.client')->getClass());

        $definition = $container->getDefinition('cleverage_rest_process.task.request');
        self::assertSame(RequestTask::class, $definition->getClass());
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());
        self::assertEquals([new Reference('logger'), new Reference('cleverage_rest_process.registry.client')], $definition->getArguments());

        // Referenced as '@<class>' in process configurations
        $alias = $container->getAlias(RequestTask::class);
        self::assertSame('cleverage_rest_process.task.request', (string) $alias);
        self::assertTrue($alias->isPublic());
    }
}
