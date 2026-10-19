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

use CleverAge\RestProcessBundle\DependencyInjection\Compiler\RegisterClientsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final since 3.3
 */
class CleverAgeRestProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry.
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterClientsPass());
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
