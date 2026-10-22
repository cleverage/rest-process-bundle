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

namespace CleverAge\RestProcessBundle\Tests\Functional;

use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use CleverAge\RestProcessBundle\Client\Client;
use CleverAge\RestProcessBundle\Registry\ClientRegistry;
use CleverAge\RestProcessBundle\Task\RequestTask;
use CleverAge\RestProcessBundle\Tests\App\TestKernel;
use CleverAge\RestProcessBundle\Tests\Fixtures\AttributedClient;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The bundle in an application with process-bundle (see TestKernel): the container compiles, the services of the
 * bundle are resolved with their dependencies.
 */
#[CoversNothing]
class KernelTest extends KernelTestCase
{
    private mixed $exceptionHandler;

    protected function setUp(): void
    {
        $this->exceptionHandler = $this->getExceptionHandler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // The ErrorHandler registered when the kernel boots is not removed by the first Symfony 6.4 versions
        if ($this->getExceptionHandler() !== $this->exceptionHandler) {
            restore_exception_handler();
        }
    }

    public function testClientsAreResolvedByCode(): void
    {
        $registry = self::getContainer()->get('test.client_registry');
        self::assertInstanceOf(ClientRegistry::class, $registry);

        self::assertInstanceOf(Client::class, $registry->getClient('app_tagged'));
        self::assertInstanceOf(AttributedClient::class, $registry->getClient('app_attributed'));
    }

    public function testTasksAreResolvedByCode(): void
    {
        $processConfigurationRegistry = self::getContainer()->get('test.process_configuration_registry');
        self::assertInstanceOf(ProcessConfigurationRegistry::class, $processConfigurationRegistry);
        $taskRegistry = self::getContainer()->get('test.task_registry');
        self::assertInstanceOf(TaskRegistry::class, $taskRegistry);

        $taskConfiguration = $processConfigurationRegistry->getProcessConfiguration('test.rest')->getTaskConfiguration('request');
        self::assertInstanceOf(RequestTask::class, $taskRegistry->getTask($taskConfiguration));
    }

    private function getExceptionHandler(): mixed
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
