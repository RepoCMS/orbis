<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Presentation\Http;

use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateHandler;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesHandler;
use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Notification\Presentation\Http\ApiNotificationTemplateController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ApiNotificationTemplateControllerTest extends TestCase
{
    private const TEMPLATE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createController(): ApiNotificationTemplateController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new ApiNotificationTemplateController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createListHandler(): ListNotificationTemplatesHandler
    {
        return new ListNotificationTemplatesHandler(
            $this->createMock(NotificationTemplateRepositoryInterface::class),
        );
    }

    private function createUpdateHandler(): UpdateNotificationTemplateHandler
    {
        $template = NotificationTemplate::create(
            id: new NotificationTemplateIdValueObject(self::TEMPLATE_ID),
            eventType: EventType::TICKET_CREATED,
            channel: NotificationChannel::EMAIL,
            subject: 'Test subject',
            bodyTemplate: 'Test body',
            isActive: true,
        );

        $repo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repo->method('findById')
            ->willReturn($template);

        return new UpdateNotificationTemplateHandler($repo);
    }

    // ─── GET /api/v1/notifications/templates ───────────────────────

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

    public function testListWithFilters(): void
    {
        $handler = $this->createListHandler();

        $request = new Request([
            'eventType' => 'ticket_created',
            'channel' => 'email',
            'isActive' => '1',
        ]);

        $response = $this->createController()->list($request, $handler);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
    }

    // ─── PUT /api/v1/notifications/templates/{id} ─────────────────

    public function testUpdateReturnsSuccess(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([
            'subject' => 'Asunto actualizado',
            'bodyTemplate' => 'Cuerpo actualizado de la plantilla.',
            'isActive' => false,
        ]));

        $response = $this->createController()->update(self::TEMPLATE_ID, $request, $handler);

        self::assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::TEMPLATE_ID, $data['data']['id']);
    }

    public function testUpdateWithMinimalFields(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([
            'bodyTemplate' => 'Solo cuerpo.',
        ]));

        $response = $this->createController()->update(self::TEMPLATE_ID, $request, $handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testUpdateReturns422WhenBodyMissing(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([
            'subject' => 'Asunto solamente',
        ]));

        $response = $this->createController()->update(self::TEMPLATE_ID, $request, $handler);

        self::assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
        self::assertStringContainsString('bodyTemplate', $data['error']);
    }

    public function testUpdateReturns422WhenBodyEmpty(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: json_encode([
            'bodyTemplate' => '',
        ]));

        $response = $this->createController()->update(self::TEMPLATE_ID, $request, $handler);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testUpdateReturns400WhenInvalidJson(): void
    {
        $handler = $this->createUpdateHandler();

        $request = new Request(content: '{not valid json}');
        $request->headers->set('Content-Type', 'application/json');

        $this->expectException(BadRequestHttpException::class);

        $this->createController()->update(self::TEMPLATE_ID, $request, $handler);
    }
}
