<?php declare(strict_types=1);
/**
 * This file is part of the CleverAge/RestProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\RestProcessBundle;

use CleverAge\ProcessBundle\DependencyInjection\Compiler\RegistryCompilerPass;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class CleverAgeRestProcessBundle
 *
 * @author  Valentin Clavreul <vclavreul@clever-age.com>
 * @author  Vincent Chalnot <vchalnot@clever-age.com>
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CleverAgeRestProcessBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into registry
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new RegistryCompilerPass(
                ClientRegistry::class,
                'cleverage.rest.client',
                'addClient'
            )
        );
    }
}
