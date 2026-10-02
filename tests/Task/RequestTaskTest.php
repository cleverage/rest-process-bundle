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

namespace CleverAge\RestProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\RestProcessBundle\Client\Client;
use CleverAge\RestProcessBundle\Exception\MissingClientException;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\RestProcessBundle\Task\RequestTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(RequestTask::class)]
#[UsesClass(Client::class)]
#[UsesClass(ClientRegistry::class)]
#[UsesClass(MissingClientException::class)]
class RequestTaskTest extends TestCase
{
    /** @var list<array{level: string, message: string, context: array<mixed>}> */
    private array $logs = [];

    /** @var list<string> */
    private array $urls = [];

    public function testOutputsTheResponseBody(): void
    {
        [$task, $state] = $this->createTask([], new MockResponse('{"id":1}'));

        $task->execute($state);

        self::assertSame('{"id":1}', $state->getOutput());
        self::assertSame(['https://example.com/api/books/1'], $this->urls);
        self::assertSame(['debug'], array_column($this->logs, 'level'));
    }

    public function testInputOverridesTheOptions(): void
    {
        [$task, $state] = $this->createTask([], new MockResponse('{}'));
        $state->setInput(['url' => '/books/{id}', 'url_parameters' => ['id' => 2]]);

        $task->execute($state);

        self::assertSame(['https://example.com/api/books/2'], $this->urls);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideValidErrorCodes(): iterable
    {
        yield 'redirection' => [304];
        yield 'client error' => [404];
        yield 'server error' => [503];
    }

    #[DataProvider('provideValidErrorCodes')]
    public function testErrorCodeDeclaredValid(int $code): void
    {
        [$task, $state] = $this->createTask(
            ['valid_response_code' => [200, $code]],
            new MockResponse('{"error":true}', ['http_code' => $code])
        );

        $task->execute($state);

        self::assertSame('{"error":true}', $state->getOutput());
        self::assertSame([], array_values(array_filter($this->logs, static fn (array $log): bool => 'error' === $log['level'])));
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function provideErrorStrategies(): iterable
    {
        yield 'skip' => [TaskConfiguration::STRATEGY_SKIP, true, false];
        yield 'stop' => [TaskConfiguration::STRATEGY_STOP, false, true];
    }

    #[DataProvider('provideErrorStrategies')]
    public function testInvalidResponseCode(string $errorStrategy, bool $skipped, bool $stopped): void
    {
        [$task, $state] = $this->createTask([], new MockResponse('{"error":"not found"}', ['http_code' => 404]), $errorStrategy);

        try {
            $task->execute($state);
            self::fail('The task must fail');
        } catch (\Exception $e) {
            self::assertSame('Invalid response code', $e->getMessage());
        }

        self::assertSame('{"error":"not found"}', $state->getErrorOutput());
        self::assertSame($skipped, $state->isSkipped());
        self::assertSame($stopped, $state->isStopped());
        $error = $this->getErrorLog();
        self::assertSame('Invalid response code', $error['message']);
        self::assertSame('{"error":"not found"}', $error['raw_body']);
        self::assertArrayHasKey('raw_headers', $error);
    }

    public function testTransportErrorIsLogged(): void
    {
        [$task, $state] = $this->createTask([], new MockResponse('', ['error' => 'Could not resolve host']));

        try {
            $task->execute($state);
            self::fail('The task must fail');
        } catch (TransportException $e) {
            self::assertStringContainsString('Could not resolve host', $e->getMessage());
        }

        $error = $this->getErrorLog();
        self::assertIsString($error['message']);
        self::assertStringContainsString('Could not resolve host', $error['message']);
        // Not available after a transport error
        self::assertArrayNotHasKey('raw_headers', $error);
        self::assertArrayNotHasKey('raw_body', $error);
    }

    public function testErrorWhileReadingTheContentIsLogged(): void
    {
        [$task, $state] = $this->createTask([], new MockResponse([new \RuntimeException('Connection reset')]));

        $this->expectException(TransportException::class);
        try {
            $task->execute($state);
        } finally {
            self::assertSame('Connection reset', $this->getErrorLog()['message']);
        }
    }

    public function testLogResponse(): void
    {
        [$task, $state] = $this->createTask(['log_response' => true], new MockResponse('{"id":2}', ['response_headers' => ['X-Id: 2']]));
        $state->setInput(['url' => '/books/2']);

        $task->execute($state);

        $log = $this->logs[1];
        self::assertSame("Response received from '/books/2'", $log['message']);
        self::assertSame(200, $log['context']['status_code']);
        self::assertSame('{"id":2}', $log['context']['content']);
        self::assertSame(['x-id' => ['2']], $log['context']['headers']);
    }

    public function testNonArrayInputIsRejected(): void
    {
        [$task, $state] = $this->createTask([], new MockResponse('{}'));
        $state->setInput('/books/2');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('RequestTask expects an array or empty input, string given');
        $task->execute($state);
    }

    public function testMissingClient(): void
    {
        [$task, $state] = $this->createTask(['client' => 'missing'], new MockResponse('{}'));

        $this->expectException(MissingClientException::class);
        $this->expectExceptionMessage('No rest client with code : missing');
        $task->execute($state);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{RequestTask, ProcessState}
     */
    private function createTask(array $options, MockResponse $response, string $errorStrategy = TaskConfiguration::STRATEGY_STOP): array
    {
        $httpClient = new MockHttpClient(function (string $method, string $url) use ($response): MockResponse {
            $this->urls[] = $url;

            return $response;
        });
        $onLog = function (string $level, string $message, array $context): void {
            $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
        };
        $logger = new class($onLog) extends AbstractLogger {
            public function __construct(private readonly \Closure $onLog)
            {
            }

            /**
             * @param array<mixed> $context
             */
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ($this->onLog)(\is_string($level) ? $level : '', (string) $message, $context);
            }
        };

        $registry = new ClientRegistry();
        $registry->addClient(new Client($httpClient, $logger, 'api', 'https://example.com/api'));

        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->reset(true);
        $state->setTaskConfiguration(new TaskConfiguration('request', RequestTask::class, $options + [
            'client' => 'api',
            'url' => '/books/{id}',
            'method' => 'GET',
            'url_parameters' => ['id' => 1],
        ], errorStrategy: $errorStrategy));

        $task = new RequestTask($logger, $registry);
        $task->initialize($state);

        return [$task, $state];
    }

    /**
     * @return array<mixed>
     */
    private function getErrorLog(): array
    {
        $errors = array_values(array_filter($this->logs, static fn (array $log): bool => 'error' === $log['level']));
        self::assertCount(1, $errors);
        self::assertSame('REST request failed', $errors[0]['message']);

        return $errors[0]['context'];
    }
}
