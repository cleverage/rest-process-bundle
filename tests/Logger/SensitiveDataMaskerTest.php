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

namespace CleverAge\RestProcessBundle\Tests\Logger;

use CleverAge\RestProcessBundle\Logger\SensitiveDataMasker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SensitiveDataMasker::class)]
class SensitiveDataMaskerTest extends TestCase
{
    public function testMask(): void
    {
        self::assertSame(
            [
                'url' => '/books/{id}',
                'headers' => [
                    'Authorization' => '***',
                    'X-Api-Key' => '***',
                    'Cookie' => '***',
                    'Accept' => 'application/json',
                    0 => 'Proxy-Authorization: ***',
                    1 => 'X-Request-Id: 42',
                ],
                'url_parameters' => ['id' => 1, 'access_token' => '***'],
                'data' => ['user' => ['login' => 'john', 'password' => '***'], 'client_secret' => '***', 'token' => null],
            ],
            SensitiveDataMasker::mask([
                'url' => '/books/{id}',
                'headers' => [
                    'Authorization' => 'Bearer secret',
                    'X-Api-Key' => 'secret',
                    'Cookie' => 'PHPSESSID=secret',
                    'Accept' => 'application/json',
                    0 => 'Proxy-Authorization: Basic secret',
                    1 => 'X-Request-Id: 42',
                ],
                'url_parameters' => ['id' => 1, 'access_token' => 'secret'],
                'data' => ['user' => ['login' => 'john', 'password' => 'secret'], 'client_secret' => 'secret', 'token' => null],
            ])
        );
    }

    public function testResponseHeaders(): void
    {
        self::assertSame(
            ['set-cookie' => '***', 'content-type' => ['application/json']],
            SensitiveDataMasker::mask(['set-cookie' => ['session=secret'], 'content-type' => ['application/json']])
        );
    }
}
