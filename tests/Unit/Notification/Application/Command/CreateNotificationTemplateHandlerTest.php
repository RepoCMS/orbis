<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Command;

use App\Notification\Application\Command\CreateNotificationTemplate\CreateNotificationTemplateCommand;
use App\Notification\Application\Command\CreateNotificationTemplate\CreateNotificationTemplateHandler;
use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Exceptions\EmptyBodyException;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateNotificationTemplateHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testHandlerCreatesTemplate(): void
    {
        $repository = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEventTypeAndChannel')
            ->with(EventType::TICKET_CREATED, NotificationChannel::EMAIL)
            ->willReturn(null);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(NotificationTemplate::class));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new CreateNotificationTemplateHandler($repository, $idGenerator);
        $result = ($handler)(new CreateNotificationTemplateCommand(
            eventType: EventType::TICKET_CREATED,
            channel: NotificationChannel::EMAIL,
            subject: 'Ticket creado: {{ticketNumber}}',
            bodyTemplate: 'Se ha creado el ticket {{ticketNumber}} para {{serviceType}}.',
            isActive: true,
        ));

        self::assertSame(self::GENERATED_ID, $result);
    }

    public function testHandlerReturnsNullWhenTemplateAlreadyExists(): void
    {
        $existing = NotificationTemplate::create(
            id: new NotificationTemplateIdValueObject(self::GENERATED_ID),
            eventType: EventType::TICKET_CREATED,
            channel: NotificationChannel::EMAIL,
            subject: 'Existing',
            bodyTemplate: 'Existing template body',
        );

        $repository = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEventTypeAndChannel')
            ->with(EventType::TICKET_CREATED, NotificationChannel::EMAIL)
            ->willReturn($existing);

        $repository->expects($this->never())
            ->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new CreateNotificationTemplateHandler($repository, $idGenerator);
        $result = ($handler)(new CreateNotificationTemplateCommand(
            eventType: EventType::TICKET_CREATED,
            channel: NotificationChannel::EMAIL,
            subject: 'Nuevo asunto',
            bodyTemplate: 'Nuevo cuerpo',
            isActive: true,
        ));

        self::assertNull($result);
    }

    public function testHandlerThrowsOnEmptyBodyTemplate(): void
    {
        $repository = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repository->expects($this->never())
            ->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new CreateNotificationTemplateHandler($repository, $idGenerator);

        $this->expectException(EmptyBodyException::class);

        ($handler)(new CreateNotificationTemplateCommand(
            eventType: EventType::TICKET_CREATED,
            channel: NotificationChannel::EMAIL,
            subject: 'Test',
            bodyTemplate: '',
            isActive: true,
        ));
    }

    public function testHandlerCreatesInactiveTemplate(): void
    {
        $repository = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEventTypeAndChannel')
            ->willReturn(null);

        $savedTemplate = null;
        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (NotificationTemplate $t) use (&$savedTemplate): void {
                $savedTemplate = $t;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new CreateNotificationTemplateHandler($repository, $idGenerator);
        ($handler)(new CreateNotificationTemplateCommand(
            eventType: EventType::TICKET_ASSIGNED,
            channel: NotificationChannel::IN_APP,
            subject: null,
            bodyTemplate: 'Notificación in-app.',
            isActive: false,
        ));

        self::assertNotNull($savedTemplate);
        self::assertFalse($savedTemplate->isActive());
        self::assertNull($savedTemplate->subject());
    }

    public function testHandlerWithDefaultIsActive(): void
    {
        $repository = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEventTypeAndChannel')
            ->willReturn(null);

        $savedTemplate = null;
        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (NotificationTemplate $t) use (&$savedTemplate): void {
                $savedTemplate = $t;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new CreateNotificationTemplateHandler($repository, $idGenerator);
        ($handler)(new CreateNotificationTemplateCommand(
            eventType: EventType::USER_REGISTERED,
            channel: NotificationChannel::EMAIL,
            subject: 'Bienvenido',
            bodyTemplate: 'Bienvenido al sistema.',
        ));

        self::assertNotNull($savedTemplate);
        self::assertTrue($savedTemplate->isActive());
    }
}
