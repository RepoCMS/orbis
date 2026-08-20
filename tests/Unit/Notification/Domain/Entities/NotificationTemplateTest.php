<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Domain\Entities;

use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Exceptions\EmptyBodyException;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use PHPUnit\Framework\TestCase;

final class NotificationTemplateTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private NotificationTemplateIdValueObject $templateId;

    protected function setUp(): void
    {
        $this->templateId = new NotificationTemplateIdValueObject(self::GENERATED_ID);
    }

    public function testCreateWithValidData(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Welcome {{username}}',
            'Hello {{username}}, welcome to our platform!',
            true,
        );

        self::assertSame($this->templateId, $template->id());
        self::assertSame(EventType::USER_REGISTERED, $template->eventType());
        self::assertSame(NotificationChannel::EMAIL, $template->channel());
        self::assertSame('Welcome {{username}}', $template->subject());
        self::assertSame('Hello {{username}}, welcome to our platform!', $template->bodyTemplate());
        self::assertTrue($template->isActive());
        self::assertNotNull($template->createdAt());
        self::assertNull($template->updatedAt());
    }

    public function testCreateWithNullSubject(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::IN_APP,
            null,
            'Hello {{username}}!',
            true,
        );

        self::assertNull($template->subject());
    }

    public function testCreateDefaultsToActive(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            'Body',
        );

        self::assertTrue($template->isActive());
    }

    public function testCreateCanBeInactive(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            'Body',
            false,
        );

        self::assertFalse($template->isActive());
    }

    public function testThrowsOnEmptyBodyTemplate(): void
    {
        $this->expectException(EmptyBodyException::class);
        $this->expectExceptionMessage('El cuerpo de la plantilla es obligatorio');

        NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            '',
        );
    }

    public function testThrowsOnWhitespaceOnlyBodyTemplate(): void
    {
        $this->expectException(EmptyBodyException::class);

        NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            '   ',
        );
    }

    public function testUpdateChangesFields(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Old Subject',
            'Old Body',
            true,
        );

        $oldCreatedAt = $template->createdAt();

        $template->update('New Subject', 'New Body {{var}}', false);

        self::assertSame('New Subject', $template->subject());
        self::assertSame('New Body {{var}}', $template->bodyTemplate());
        self::assertFalse($template->isActive());
        self::assertSame($oldCreatedAt, $template->createdAt());
        // Note: entity does not set updatedAt automatically - this is expected behavior
        self::assertNull($template->updatedAt());
    }

    public function testUpdateThrowsOnEmptyBodyTemplate(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            'Body',
        );

        $this->expectException(EmptyBodyException::class);
        $this->expectExceptionMessage('El cuerpo de la plantilla es obligatorio');

        $template->update('Subject', '', false);
    }

    public function testUpdateThrowsOnWhitespaceOnlyBodyTemplate(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            'Body',
        );

        $this->expectException(EmptyBodyException::class);

        $template->update('Subject', '   ', false);
    }

    public function testUpdatePreservesIdAndCreatedAt(): void
    {
        $template = NotificationTemplate::create(
            $this->templateId,
            EventType::USER_REGISTERED,
            NotificationChannel::EMAIL,
            'Subject',
            'Body',
        );

        $id = $template->id();
        $createdAt = $template->createdAt();

        $template->update('New Subject', 'New Body', false);

        self::assertSame($id, $template->id());
        self::assertSame($createdAt, $template->createdAt());
    }
}
