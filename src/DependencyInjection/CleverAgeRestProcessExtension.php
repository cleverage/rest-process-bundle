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

namespace CleverAge\RestProcessBundle\DependencyInjection;

use CleverAge\RestProcessBundle\Attribute\AsRestClient;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
class CleverAgeRestProcessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->findServices($container, __DIR__.'/../../config/services');

        $container->registerAttributeForAutoconfiguration(
            AsRestClient::class,
            static function (ChildDefinition $definition, AsRestClient $attribute): void {
                $definition->addTag('cleverage.rest.client', ['code' => $attribute->code]);
            }
        );
    }

    /**
     * Recursively import config files into container.
     */
    protected function findServices(ContainerBuilder $container, string $path, string $extension = 'yaml'): void
    {
        $finder = new Finder();
        $finder->in($path)
            ->name('*.'.$extension)->files();
        $loader = new YamlFileLoader($container, new FileLocator($path));
        foreach ($finder as $file) {
            $loader->load($file->getFilename());
        }
    }
}
