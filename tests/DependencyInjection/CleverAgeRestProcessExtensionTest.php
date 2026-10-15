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

use CleverAge\RestProcessBundle\Attribute\AsRestClient;
use CleverAge\RestProcessBundle\DependencyInjection\CleverAgeRestProcessExtension;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\RestProcessBundle\Task\RequestTask;
use CleverAge\RestProcessBundle\Tests\Fixtures\AttributedClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\AttributeAutoconfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(CleverAgeRestProcessExtension::class)]
#[UsesClass(AsRestClient::class)]
class CleverAgeRestProcessExtensionTest extends TestCase
{
    public function testServices(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeRestProcessExtension())->load([], $container);

        self::assertSame(ClientRegistry::class, $container->getDefinition('cleverage_rest_process.registry.client')->getClass());
        // Autowirable by its class name
        self::assertSame('cleverage_rest_process.registry.client', (string) $container->getAlias(ClientRegistry::class));

        // The class name is the service id
        $definition = $container->getDefinition(RequestTask::class);
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());
        self::assertEquals([new Reference('logger'), new Reference('cleverage_rest_process.registry.client')], $definition->getArguments());
        self::assertSame([['channel' => 'cleverage_process_task']], $definition->getTag('monolog.logger'));
        // Referenced by its code in the process configurations (task: <code>)
        self::assertSame([['code' => 'rest_request']], $definition->getTag('cleverage.task'));
        // Public for the direct access from the service container, deprecated
        self::assertTrue($definition->isPublic());
        self::assertSame([['package' => 'cleverage/rest-process-bundle', 'version' => '3.3']], $definition->getTag('container.private'));

        // Former id, deprecated
        $deprecatedId = 'cleverage_rest_process.task.request';
        $alias = $container->getAlias($deprecatedId);
        self::assertSame(RequestTask::class, (string) $alias);
        self::assertFalse($alias->isPublic());
        self::assertTrue($alias->isDeprecated());
        self::assertSame(
            ['package' => 'cleverage/rest-process-bundle', 'version' => '3.3', 'message' => 'The "'.$deprecatedId.'" service alias is deprecated, use the "rest_request" task code or the class name of the task instead. It will be removed in 4.0.'],
            $alias->getDeprecation($deprecatedId)
        );
    }

    public function testAsRestClientAttributeRegistersTheCode(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeRestProcessExtension())->load([], $container);
        $container->setDefinition('app.attributed', (new Definition(AttributedClient::class))->setAutoconfigured(true));

        (new AttributeAutoconfigurationPass())->process($container);
        (new ResolveInstanceofConditionalsPass())->process($container);
        (new ResolveChildDefinitionsPass())->process($container);

        self::assertSame([['code' => 'app_attributed']], $container->getDefinition('app.attributed')->getTag('cleverage.rest.client'));
    }
}
