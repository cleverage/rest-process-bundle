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

namespace CleverAge\RestProcessBundle\Exception;

/**
 * Exception thrown when trying to fetch a missing Rest client.
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class MissingClientException extends RestException
{
    /**
     * @param string $code
     *
     * @return MissingClientException
     */
    public static function create($code)
    {
        $errorStr = "No rest client with code : {$code}";

        return new self($errorStr);
    }
}
