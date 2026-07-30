<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Presentation\Http;

use App\Notification\Application\Command\RetryNotification\RetryNotificationHandler;
use App\Notification\Application\Exception\InvalidNotificationStatusException;
use App\Notification\Application\Exception\NotificationLogNotFoundException;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsHandler;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Presentation\Http\ApiNotificationLogController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ApiNotificationLogControllerTest extends TestCase
{
    private const LOG_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createController(): ApiNotificationLogController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new ApiNotificationLogController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createListHandler(): ListNotificationLogsHandler
    {
        return new ListNotificationLogsHandler(
            $this->createMock(NotificationLogRepositoryInterface::class),
        );
    }

    private function createRetryHandler(): RetryNotificationHandler
    {
        return new RetryNotificationHandler(
            $this->createMock(NotificationLogRepositoryInterface::class),
        );
    }

    // ─── GET /api/v1/notifications/logs ────────────────────────────

    public function testListReturnsData(): void
    {
        $handler = $this->createListHandler();

        $response = $this->createController()->list(new Request(), $handler);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame([], $data['data']);
    }

    public function testListWithAllFilters(): void
    {
        $handler = $this->createListHandler();

        $request = new Request([
            'status' => 'failed',
            'recipientType' => 'employee',
            'recipientId' => self::LOG_ID,
            'eventType' => 'ticket_created',
            'dateFrom' => '2024-01-01',
            'dateTo' => '2024-12-31',
            'page' => '2',
            'limit' => '10',
        ]);

        $response = $this->createController()->list($request, $handler);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
    }

    // ─── POST /api/v1/notifications/logs/{id}/retry ────────────────

    public function testRetryReturnsSuccess(): void
    {
        // Create a real handler with a repository that will find and retry a failed log
        $log = \App\Notification\Domain\Entities\NotificationLog::create(
            id: new \App\Notification\Domain\ValueObjects\NotificationLogIdValueObject(self::LOG_ID),
            recipientType: \App\Notification\Domain\Enums\RecipientType::EMPLOYEE,
            recipientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            channel: \App\Notification\Domain\Enums\NotificationChannel::EMAIL,
            eventType: \App\Notification\Domain\Enums\EventType::TICKET_CREATED,
            subject: 'Test',
            body: 'Body',
        );
        $log->markFailed('Error 1');
        $log->markFailed('Error 2');
        $log->markFailed('Error 3'); // now FAILED

        $repo = $this->createMock(NotificationLogRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->willReturn($log);
        $repo->expects($this->once())
            ->method('save');

        $handler = new RetryNotificationHandler($repo);

        $response = $this->createController()->retry(self::LOG_ID, $handler);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::LOG_ID, $data['data']['id']);
    }

    public function testRetryReturns404WhenLogNotFound(): void
    {
        $repo = $this->createMock(NotificationLogRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $handler = new RetryNotificationHandler($repo);

        $response = $this->createController()->retry(self::LOG_ID, $handler);

        self::assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testRetryReturns400WhenNotFailed(): void
    {
        $log = \App\Notification\Domain\Entities\NotificationLog::create(
            id: new \App\Notification\Domain\ValueObjects\NotificationLogIdValueObject(self::LOG_ID),
            recipientType: \App\Notification\Domain\Enums\RecipientType::EMPLOYEE,
            recipientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            channel: \App\Notification\Domain\Enums\NotificationChannel::EMAIL,
            eventType: \App\Notification\Domain\Enums\EventType::TICKET_CREATED,
            subject: 'Test',
            body: 'Body',
        );
        $log->markSent(); // SENT status, not FAILED

        $repo = $this->createMock(NotificationLogRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->willReturn($log);

        $handler = new RetryNotificationHandler($repo);

        $response = $this->createController()->retry(self::LOG_ID, $handler);

        self::assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }
}
