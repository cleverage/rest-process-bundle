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
use CleverAge\RestProcessBundle\DependencyInjection\Configuration;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\RestProcessBundle\Task\RequestTask;
use CleverAge\RestProcessBundle\Tests\Fixtures\AttributedClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\AttributeAutoconfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(CleverAgeRestProcessExtension::class)]
#[CoversClass(Configuration::class)]
#[CoversClass(AsRestClient::class)]
class CleverAgeRestProcessExtensionTest extends TestCase
{
    public function testEveryServiceDefinitionFileIsLoaded(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeRestProcessExtension())->load([], $container);

        $files = glob(\dirname(__DIR__, 2).'/config/services/*.yaml');
        self::assertIsArray($files);
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $config = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);
            self::assertIsArray($config);
            if (!isset($config['services'])) {
                continue;
            }
            self::assertIsArray($config['services']);
            foreach (array_keys($config['services']) as $id) {
                if ('_defaults' === $id) {
                    continue;
                }
                if (str_ends_with((string) $id, '\\')) {
                    // Resource: at least one service registered under the namespace prefix
                    $registered = array_filter(
                        array_keys($container->getDefinitions()),
                        static fn (string $definitionId): bool => str_starts_with($definitionId, (string) $id)
                    );
                    self::assertNotEmpty($registered, \sprintf('No service registered for the "%s" resource of %s', $id, basename($file)));
                    continue;
                }
                self::assertTrue($container->has((string) $id), \sprintf('Service "%s" of %s is not registered', $id, basename($file)));
            }
        }
    }

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

    public function testConfigurationKeysAreDeprecated(): void
    {
        $deprecations = $this->collectDeprecations(static function (): void {
            (new CleverAgeRestProcessExtension())->load([[], ['foo' => true], ['bar' => 1, 'foo' => false]], new ContainerBuilder());
        });

        self::assertSame(['Since cleverage/rest-process-bundle 3.3: Configuring "cleverage_rest_process" is deprecated, the bundle has no configuration: the keys "foo", "bar" are ignored. They will be rejected in 4.0.'], $deprecations);
    }

    public function testNoDeprecationWithoutConfigurationKey(): void
    {
        $deprecations = $this->collectDeprecations(static function (): void {
            (new CleverAgeRestProcessExtension())->load([[], []], new ContainerBuilder());
        });

        self::assertSame([], $deprecations);
    }

    public function testConfigurationHasNoOption(): void
    {
        $configuration = (new CleverAgeRestProcessExtension())->getConfiguration([], new ContainerBuilder());

        self::assertInstanceOf(Configuration::class, $configuration);
        self::assertSame('cleverage_rest_process', $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }

    /**
     * @param callable(): void $callback
     *
     * @return list<string>
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
