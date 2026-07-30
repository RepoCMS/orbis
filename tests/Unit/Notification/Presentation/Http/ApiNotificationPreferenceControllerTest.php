<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Presentation\Http;

use App\Notification\Application\Command\UpdateNotificationPreference\UpdateNotificationPreferenceHandler;
use App\Notification\Application\Query\ListNotificationPreferences\ListNotificationPreferencesHandler;
use App\Notification\Domain\Entities\NotificationPreference;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Repositories\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;
use App\Notification\Presentation\Http\ApiNotificationPreferenceController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ApiNotificationPreferenceControllerTest extends TestCase
{
    private const PREFERENCE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const RECIPIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FB1';

    private function createController(): ApiNotificationPreferenceController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new ApiNotificationPreferenceController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createListHandler(): ListNotificationPreferencesHandler
    {
        return new ListNotificationPreferencesHandler(
            $this->createMock(NotificationPreferenceRepositoryInterface::class),
        );
    }

    private function createUpdateHandler(): UpdateNotificationPreferenceHandler
    {
        $preference = NotificationPreference::create(
            id: new NotificationPreferenceIdValueObject(self::PREFERENCE_ID),
            recipientType: RecipientType::EMPLOYEE,
            recipientId: self::RECIPIENT_ID,
            channel: NotificationChannel::EMAIL,
            eventType: EventType::TICKET_CREATED,
            isEnabled: true,
        );

        $repo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $repo->method('findById')
            ->willReturn($preference);

        return new UpdateNotificationPreferenceHandler($repo);
    }

    // ─── GET /api/v1/notifications/preferences ─────────────────────

    public function testListReturnsData(): void
    {
        $handler = $this->createListHandler();

        $request = new Request([
            'recipientType' => 'employee',
            'recipientId' => self::RECIPIENT_ID,
        ]);

        $response = $this->createController()->list($request, $handler);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame([], $data['data']);
    }

    public function testListReturns422WhenParamsMissing(): void
    {
        $handler = $this->createListHandler();

        $request = new Request();

        $response = $this->createController()->list($request, $handler);

        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testListReturns422WhenRecipientTypeMissing(): void
    {
        $handler = $this->createListHandler();

        $request = new Request(['recipientId' => self::RECIPIENT_ID]);

        $response = $this->createController()->list($request, $handler);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testListReturns422WhenRecipientIdMissing(): void
    {
        $handler = $this->createListHandler();

        $request = new Request(['recipientType' => 'employee']);

        $response = $this->createController()->list($request, $handler);

        self::assertSame(422, $response->getStatusCode());
    }

    // ─── PATCH /api/v1/notifications/preferences/{id} ─────────────

    public function testUpdateReturnsSuccess(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([
            'isEnabled' => false,
        ]));

        $response = $this->createController()->update(self::PREFERENCE_ID, $request, $handler);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::PREFERENCE_ID, $data['data']['id']);
    }

    public function testUpdateReturns422WhenIsEnabledMissing(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([]));

        $response = $this->createController()->update(self::PREFERENCE_ID, $request, $handler);

        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
        self::assertStringContainsString('isEnabled', $data['error']);
    }

    public function testUpdateReturns422WhenIsEnabledNotBool(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([
            'isEnabled' => 'not-a-boolean',
        ]));

        $response = $this->createController()->update(self::PREFERENCE_ID, $request, $handler);

        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testUpdateReturns400WhenInvalidJson(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: '{not valid}');
        $request->headers->set('Content-Type', 'application/json');

        $this->expectException(BadRequestHttpException::class);

        $this->createController()->update(self::PREFERENCE_ID, $request, $handler);
    }
}
