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

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @phpstan-import-type RequestOptions from \CleverAge\RestProcessBundle\Task\RequestTask
 */
interface ClientInterface
{
    /**
     * Return the code of the client used in client registry.
     */
    public function getCode(): string;

    public function geUri(): string;

    public function setUri(string $uri): void;

    /**
     * @param RequestOptions $options
     */
    public function call(array $options = []): ResponseInterface;
}
