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

namespace CleverAge\RestProcessBundle\Attribute;

/**
 * Registers a REST client service under its code, so that it is only instantiated when a process uses it.
 *
 * Requires autoconfiguration; equivalent to a `cleverage.rest.client` tag with a `code` attribute. The code must be
 * the one returned by `ClientInterface::getCode()`.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsRestClient
{
    public function __construct(
        public string $code,
    ) {
    }
}
