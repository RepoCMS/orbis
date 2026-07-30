<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Presentation\Http;

use App\Notification\Application\Command\MarkAllNotificationsRead\MarkAllNotificationsReadHandler;
use App\Notification\Application\Command\MarkNotificationRead\MarkNotificationReadHandler;
use App\Notification\Application\Query\CountUnreadNotifications\CountUnreadNotificationsHandler;
use App\Notification\Application\Query\ListInboxNotifications\ListInboxNotificationsHandler;
use App\Notification\Application\Service\SecurityUserResolverInterface;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Presentation\Http\ApiNotificationInboxController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ApiNotificationInboxControllerTest extends TestCase
{
    private const EMPLOYEE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FB1';
    private const LOG_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createUserResolver(?string $userId = self::EMPLOYEE_ID): SecurityUserResolverInterface
    {
        $resolver = $this->createMock(SecurityUserResolverInterface::class);
        $resolver->method('getUserId')->willReturn($userId);

        return $resolver;
    }

    private function createController(): ApiNotificationInboxController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new ApiNotificationInboxController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createListHandler(): ListInboxNotificationsHandler
    {
        return new ListInboxNotificationsHandler(
            $this->createMock(NotificationLogRepositoryInterface::class),
        );
    }

    private function createCountHandler(): CountUnreadNotificationsHandler
    {
        return new CountUnreadNotificationsHandler(
            $this->createMock(NotificationLogRepositoryInterface::class),
        );
    }

    private function createMarkReadHandler(): MarkNotificationReadHandler
    {
        return new MarkNotificationReadHandler(
            $this->createMock(NotificationLogRepositoryInterface::class),
        );
    }

    private function createMarkAllReadHandler(): MarkAllNotificationsReadHandler
    {
        return new MarkAllNotificationsReadHandler(
            $this->createMock(NotificationLogRepositoryInterface::class),
        );
    }

    // ─── GET /api/v1/notifications/inbox ──────────────────────────

    public function testListReturnsInbox(): void
    {
        $listHandler = $this->createListHandler();
        $userResolver = $this->createUserResolver();

        $response = $this->createController()->list(new Request(), $listHandler, $userResolver);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('unreadCount', $data);
    }

    public function testListReturns401WhenNotAuthenticated(): void
    {
        $listHandler = $this->createListHandler();
        $userResolver = $this->createUserResolver(null);

        $response = $this->createController()->list(new Request(), $listHandler, $userResolver);

        self::assertSame(401, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testListWithUnreadOnlyFilter(): void
    {
        $listHandler = $this->createListHandler();
        $userResolver = $this->createUserResolver();

        $request = new Request(['unreadOnly' => '1']);

        $response = $this->createController()->list($request, $listHandler, $userResolver);

        self::assertSame(200, $response->getStatusCode());
    }

    // ─── GET /api/v1/notifications/inbox/unread-count ─────────────

    public function testUnreadCountReturnsCount(): void
    {
        $repo = $this->createMock(NotificationLogRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('countUnread')
            ->with(self::EMPLOYEE_ID)
            ->willReturn(5);

        $countHandler = new CountUnreadNotificationsHandler($repo);
        $userResolver = $this->createUserResolver();

        $response = $this->createController()->unreadCount($countHandler, $userResolver);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('unreadCount', $data['data']);
        self::assertSame(5, $data['data']['unreadCount']);
    }

    public function testUnreadCountReturns401WhenNotAuthenticated(): void
    {
        $countHandler = $this->createCountHandler();
        $userResolver = $this->createUserResolver(null);

        $response = $this->createController()->unreadCount($countHandler, $userResolver);

        self::assertSame(401, $response->getStatusCode());
    }

    // ─── POST /api/v1/notifications/inbox/{id}/read ───────────────

    public function testMarkReadReturnsSuccess(): void
    {
        // Create a real notification log to be read
        $log = \App\Notification\Domain\Entities\NotificationLog::create(
            id: new \App\Notification\Domain\ValueObjects\NotificationLogIdValueObject(self::LOG_ID),
            recipientType: \App\Notification\Domain\Enums\RecipientType::EMPLOYEE,
            recipientId: self::EMPLOYEE_ID,
            channel: \App\Notification\Domain\Enums\NotificationChannel::IN_APP,
            eventType: \App\Notification\Domain\Enums\EventType::TICKET_CREATED,
            subject: 'Test',
            body: 'Body',
        );

        $repo = $this->createMock(NotificationLogRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->willReturn($log);
        $repo->expects($this->once())
            ->method('save');

        $markReadHandler = new MarkNotificationReadHandler($repo);
        $userResolver = $this->createUserResolver();

        $response = $this->createController()->markRead(self::LOG_ID, $markReadHandler, $userResolver);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::LOG_ID, $data['data']['id']);
    }

    public function testMarkReadReturns401WhenNotAuthenticated(): void
    {
        $markReadHandler = $this->createMarkReadHandler();
        $userResolver = $this->createUserResolver(null);

        $response = $this->createController()->markRead(self::LOG_ID, $markReadHandler, $userResolver);

        self::assertSame(401, $response->getStatusCode());
    }

    // ─── POST /api/v1/notifications/inbox/read-all ────────────────

    public function testMarkAllReadReturnsSuccess(): void
    {
        $markAllHandler = $this->createMarkAllReadHandler();
        $userResolver = $this->createUserResolver();

        $response = $this->createController()->markAllRead($markAllHandler, $userResolver);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::EMPLOYEE_ID, $data['data']['id']);
    }

    public function testMarkAllReadReturns401WhenNotAuthenticated(): void
    {
        $markAllHandler = $this->createMarkAllReadHandler();
        $userResolver = $this->createUserResolver(null);

        $response = $this->createController()->markAllRead($markAllHandler, $userResolver);

        self::assertSame(401, $response->getStatusCode());
    }
}
