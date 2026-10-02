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

namespace CleverAge\RestProcessBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds the tagged REST clients to the client registry, with their service id.
 */
class RegisterClientsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('cleverage_rest_process.registry.client')) {
            return;
        }

        $definition = $container->findDefinition('cleverage_rest_process.registry.client');
        foreach (array_keys($container->findTaggedServiceIds('cleverage.rest.client')) as $id) {
            $definition->addMethodCall('addClient', [new Reference($id), $id]);
        }
    }
}
