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

namespace CleverAge\RestProcessBundle\Tests\Fixtures;

use CleverAge\RestProcessBundle\Attribute\AsRestClient;
use CleverAge\RestProcessBundle\Client\Client;

#[AsRestClient(code: 'app_attributed')]
final class AttributedClient extends Client
{
}
