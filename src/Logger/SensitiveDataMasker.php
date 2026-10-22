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

namespace CleverAge\RestProcessBundle\Logger;

/**
 * Masks the credentials of the request and response data before logging them: the logs can be stored and displayed
 * elsewhere (e.g. by the UI bundle, to every user).
 *
 * @internal
 */
final class SensitiveDataMasker
{
    public const MASK = '***';

    /**
     * Names of the keys (header names, data and URL parameters keys) whose value is masked.
     */
    private const SENSITIVE_KEY_PATTERN = '/authorization|cookie|password|passwd|secret|token|api[-_.]?key|signature|credential|session/i';

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public static function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (\is_string($key) && null !== $value && preg_match(self::SENSITIVE_KEY_PATTERN, $key)) {
                $data[$key] = self::MASK;
            } elseif (\is_array($value)) {
                $data[$key] = self::mask($value);
            } elseif (\is_string($value) && \is_int($key)) {
                // Header given as a "Name: value" string
                $data[$key] = self::maskHeaderLine($value);
            }
        }

        return $data;
    }

    private static function maskHeaderLine(string $line): string
    {
        if (!preg_match('/^([\w-]+)\s*:/', $line, $matches) || !preg_match(self::SENSITIVE_KEY_PATTERN, $matches[1])) {
            return $line;
        }

        return $matches[1].': '.self::MASK;
    }
}
