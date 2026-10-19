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

namespace CleverAge\RestProcessBundle\Tests\App;

use CleverAge\ProcessBundle\CleverAgeProcessBundle;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\RestProcessBundle\CleverAgeRestProcessBundle;
use CleverAge\RestProcessBundle\Client\Client;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\RestProcessBundle\Tests\Fixtures\AttributedClient;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Application used by the integration tests: process-bundle and this bundle, with a client declared by tag and one by
 * attribute, in a temporary directory of its own (per PHP process).
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new CleverAgeProcessBundle();
        yield new CleverAgeRestProcessBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getRunDir().'/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getRunDir().'/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'validation' => ['email_validation_mode' => 'html5'],
        ]);
        if (Kernel::VERSION_ID >= 70300) {
            // Not setting it is deprecated since Symfony 7.3: its default value changes in 8.0
            $container->extension('framework', ['property_info' => ['with_constructor_extractor' => true]]);
        }

        $services = $container->services();
        $services->set('app.rest_client', Client::class)
            ->args([service('http_client'), service('logger'), 'app_tagged', 'https://example.com'])
            ->tag('cleverage.rest.client', ['code' => 'app_tagged']);
        $services->set(AttributedClient::class)
            ->autoconfigure()
            ->args([service('http_client'), service('logger'), 'app_attributed', 'https://example.com']);
        // Exposed to the tests
        $services->alias('test.client_registry', ClientRegistry::class)->public();

        // Tasks referenced by code (task: <code>): process-bundle 5.4 or later
        if (class_exists(TaskRegistry::class)) {
            $container->extension('clever_age_process', [
                'configurations' => [
                    'test.rest' => [
                        'tasks' => [
                            'request' => [
                                'task' => 'rest_request',
                                'options' => ['client' => 'app_tagged', 'url' => '/'],
                            ],
                        ],
                    ],
                ],
            ]);
            $services->alias('test.task_registry', TaskRegistry::class)->public();
            $services->alias('test.process_configuration_registry', ProcessConfigurationRegistry::class)->public();
        }
    }

    private function getRunDir(): string
    {
        return sys_get_temp_dir().'/rest_process_bundle_tests_'.getmypid();
    }
}
