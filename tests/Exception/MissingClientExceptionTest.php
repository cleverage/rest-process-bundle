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

namespace CleverAge\RestProcessBundle\Tests\Exception;

use CleverAge\RestProcessBundle\Exception\MissingClientException;
use CleverAge\RestProcessBundle\Exception\RestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MissingClientException::class)]
class MissingClientExceptionTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = MissingClientException::create('api');

        self::assertInstanceOf(RestException::class, $exception);
        self::assertSame('No rest client with code : api', $exception->getMessage());
    }
}
