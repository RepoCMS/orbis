<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Domain\Entities;

use App\Notification\Domain\Entities\NotificationPreference;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Exceptions\CannotDisableInAppException;
use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;
use PHPUnit\Framework\TestCase;

final class NotificationPreferenceTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private NotificationPreferenceIdValueObject $preferenceId;

    protected function setUp(): void
    {
        $this->preferenceId = new NotificationPreferenceIdValueObject(self::GENERATED_ID);
    }

    public function testCreateWithValidData(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            true,
        );

        self::assertSame($this->preferenceId, $preference->id());
        self::assertSame(RecipientType::USER, $preference->recipientType());
        self::assertSame('user-123', $preference->recipientId());
        self::assertSame(NotificationChannel::EMAIL, $preference->channel());
        self::assertSame(EventType::USER_REGISTERED, $preference->eventType());
        self::assertTrue($preference->isEnabled());
    }

    public function testCreateDefaultsToEnabled(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
        );

        self::assertTrue($preference->isEnabled());
    }

    public function testCreateCanBeDisabled(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            false,
        );

        self::assertFalse($preference->isEnabled());
    }

    public function testToggleEnables(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            false,
        );

        self::assertFalse($preference->isEnabled());

        $preference->toggle(true);

        self::assertTrue($preference->isEnabled());
    }

    public function testToggleDisables(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            true,
        );

        self::assertTrue($preference->isEnabled());

        $preference->toggle(false);

        self::assertFalse($preference->isEnabled());
    }

    public function testThrowsWhenDisablingInAppForEmployee(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::EMPLOYEE,
            'emp-456',
            NotificationChannel::IN_APP,
            EventType::TICKET_ASSIGNED,
            true,
        );

        $this->expectException(CannotDisableInAppException::class);
        $this->expectExceptionMessage('Las notificaciones IN_APP son obligatorias para empleados');

        $preference->toggle(false);
    }

    public function testCanEnableInAppForEmployee(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::EMPLOYEE,
            'emp-456',
            NotificationChannel::IN_APP,
            EventType::TICKET_ASSIGNED,
            false,
        );

        self::assertFalse($preference->isEnabled());

        $preference->toggle(true);

        self::assertTrue($preference->isEnabled());
    }

    public function testCanDisableInAppForNonEmployee(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::IN_APP,
            EventType::USER_REGISTERED,
            true,
        );

        self::assertTrue($preference->isEnabled());

        $preference->toggle(false);

        self::assertFalse($preference->isEnabled());
    }

    public function testCanDisableEmailForEmployee(): void
    {
        $preference = NotificationPreference::create(
            $this->preferenceId,
            RecipientType::EMPLOYEE,
            'emp-456',
            NotificationChannel::EMAIL,
            EventType::TICKET_ASSIGNED,
            true,
        );

        self::assertTrue($preference->isEnabled());

        $preference->toggle(false);

        self::assertFalse($preference->isEnabled());
    }
}
