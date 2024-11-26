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

namespace CleverAge\RestProcessBundle;

use CleverAge\ProcessBundle\DependencyInjection\Compiler\RegistryCompilerPass;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CleverAgeRestProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry.
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new RegistryCompilerPass(
                'cleverage_rest_process.registry.client',
                'cleverage.rest.client',
                'addClient'
            )
        );
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
