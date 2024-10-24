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

namespace CleverAge\RestProcessBundle\Client;

use Httpful\Response;

/**
 * Interface ClientInterface.
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
interface ClientInterface
{
    /**
     * Return the code of the client used in client registry.
     */
    public function getCode(): string;

    /**
     * Return the URI.
     */
    public function geUri(): string;

    /**
     * Set the URI.
     */
    public function setUri(string $uri): void;

    public function call(array $options = []): Response;
}
