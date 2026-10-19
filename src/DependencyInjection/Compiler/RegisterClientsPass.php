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
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the `cleverage.rest.client` tagged services in the client registry: in a service locator indexed by the
 * `code` attribute of the tag (lazy), or, without `code` (deprecated), with an addClient() call (eager).
 *
 * @internal
 */
class RegisterClientsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('cleverage_rest_process.registry.client')) {
            return;
        }
        $definition = $container->findDefinition('cleverage_rest_process.registry.client');

        $services = [];
        $ids = [];
        foreach ($container->findTaggedServiceIds('cleverage.rest.client') as $id => $tags) {
            $codes = [];
            foreach ($tags as $attributes) {
                if (!\is_array($attributes) || !isset($attributes['code'])) {
                    continue;
                }
                if (!\is_string($attributes['code']) || '' === $attributes['code']) {
                    throw new InvalidArgumentException(\sprintf('The "code" attribute of the "cleverage.rest.client" tag of the "%s" service must be a non-empty string.', $id));
                }
                $codes[] = $attributes['code'];
            }
            if ([] === $codes) {
                // Deprecated: instantiated with the registry, addClient() triggers the deprecation
                $definition->addMethodCall('addClient', [new Reference($id), $id]);
                continue;
            }
            foreach (array_unique($codes) as $code) {
                if (isset($ids[$code])) {
                    throw new InvalidArgumentException(\sprintf('The REST client code "%s" is used by the "%s" and "%s" services.', $code, $ids[$code], $id));
                }
                $ids[$code] = $id;
                $services[$code] = new Reference($id);
            }
        }

        $definition->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
