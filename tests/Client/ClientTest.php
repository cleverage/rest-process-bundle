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

namespace CleverAge\RestProcessBundle\Tests\Client;

use CleverAge\RestProcessBundle\Client\Client;
use CleverAge\RestProcessBundle\Exception\RestRequestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(Client::class)]
#[UsesClass(RestRequestException::class)]
class ClientTest extends TestCase
{
    /** @var list<array{method: string, url: string, options: array<mixed>}> */
    private array $requests = [];

    public function testAccessors(): void
    {
        $client = $this->createClient();

        self::assertSame('api', $client->getCode());
        self::assertSame('https://example.com/api', $client->getUri());
        self::assertSame('https://example.com/api', $client->geUri());
        self::assertInstanceOf(NullLogger::class, $client->getLogger());

        $client->setUri('https://other.example.com');
        self::assertSame('https://other.example.com', $client->getUri());
    }

    public function testGetWithQueryAndUrlParameters(): void
    {
        $response = $this->call($this->createClient(), [
            'url' => '/communes/{code}/{name}',
            'url_parameters' => ['code' => 46800, 'name' => 'a b/c'],
            'data' => ['page' => 2],
        ]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('GET', $this->requests[0]['method']);
        // Scalar url parameters are converted to strings and encoded, data is sent as query string
        self::assertSame('https://example.com/api/communes/46800/a%20b%2Fc?page=2', $this->requests[0]['url']);
        self::assertContains('Accept: application/json', $this->getHeaders());
        self::assertContains('Content-Type: application/json', $this->getHeaders());
    }

    public function testPostJson(): void
    {
        $this->call($this->createClient(), ['url' => 'books', 'method' => 'POST', 'data' => ['title' => 'It']]);

        self::assertSame('POST', $this->requests[0]['method']);
        self::assertSame('https://example.com/api/books', $this->requests[0]['url']);
        self::assertSame('{"title":"It"}', $this->requests[0]['options']['body']);
    }

    public function testRawBody(): void
    {
        $this->call($this->createClient(), [
            'url' => 'books',
            'method' => 'POST',
            'sends' => 'text/plain',
            'expects' => '',
            'headers' => ['X-Token' => 'secret'],
            'data' => 'raw content',
        ]);

        self::assertSame('raw content', $this->requests[0]['options']['body']);
        self::assertContains('X-Token: secret', $this->getHeaders());
        self::assertContains('Content-Type: text/plain', $this->getHeaders());
        self::assertNotContains('Accept: application/json', $this->getHeaders());
    }

    public function testInvalidMethod(): void
    {
        $this->expectException(RestRequestException::class);
        $this->expectExceptionMessage('FETCH is not an HTTP method');
        $this->call($this->createClient(), ['url' => 'books', 'method' => 'FETCH']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidUrlParameters(): iterable
    {
        yield 'array' => [['46800']];
        yield 'null' => [null];
    }

    #[DataProvider('provideInvalidUrlParameters')]
    public function testInvalidUrlParameter(mixed $value): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('URL parameters must be scalar values');
        $this->call($this->createClient(), ['url' => '/communes/{code}', 'url_parameters' => ['code' => $value]]);
    }

    public function testUnknownOption(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->call($this->createClient(), ['url' => 'books', 'client' => 'api']);
    }

    public function testRequestFailure(): void
    {
        $httpClient = new MockHttpClient(static function (): never {
            throw new \RuntimeException('Invalid request');
        });
        $client = new Client($httpClient, new NullLogger(), 'api', 'https://example.com/api');

        $this->expectException(RestRequestException::class);
        $this->expectExceptionMessage('Rest request failed');
        $this->call($client, ['url' => 'books']);
    }

    /**
     * @param array<string, mixed> $options Partial options, completed with the defaults by the client
     */
    private function call(Client $client, array $options): ResponseInterface
    {
        // @phpstan-ignore argument.type
        return $client->call($options);
    }

    private function createClient(): Client
    {
        $httpClient = new MockHttpClient(
            /**
             * @param array<mixed> $options
             */
            function (string $method, string $url, array $options): MockResponse {
                $this->requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

                return new MockResponse('[]');
            }
        );

        return new Client($httpClient, new NullLogger(), 'api', 'https://example.com/api');
    }

    /**
     * @return list<string>
     */
    private function getHeaders(): array
    {
        /** @var list<string> $headers */
        $headers = $this->requests[0]['options']['headers'] ?? [];

        return $headers;
    }
}
