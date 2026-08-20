<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Domain\Entities;

use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\NotificationStatus;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Exceptions\CannotMarkReadNonInAppException;
use App\Notification\Domain\Exceptions\EmptyBodyException;
use App\Notification\Domain\Exceptions\EmptyRecipientException;
use App\Notification\Domain\Exceptions\InvalidNotificationStatusTransitionException;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use PHPUnit\Framework\TestCase;

final class NotificationLogTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private NotificationLogIdValueObject $logId;

    protected function setUp(): void
    {
        $this->logId = new NotificationLogIdValueObject(self::GENERATED_ID);
    }

    public function testCreateWithValidData(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Welcome',
            'Welcome to our platform',
        );

        self::assertSame($this->logId, $log->id());
        self::assertSame(RecipientType::USER, $log->recipientType());
        self::assertSame('user-123', $log->recipientId());
        self::assertSame(NotificationChannel::EMAIL, $log->channel());
        self::assertSame(EventType::USER_REGISTERED, $log->eventType());
        self::assertSame('Welcome', $log->subject());
        self::assertSame('Welcome to our platform', $log->body());
        self::assertSame(NotificationStatus::PENDING, $log->status());
        self::assertSame(0, $log->attemptCount());
        self::assertNull($log->lastAttemptAt());
        self::assertNull($log->sentAt());
        self::assertNull($log->failureReason());
        self::assertNull($log->referenceType());
        self::assertNull($log->referenceId());
        self::assertFalse($log->isRead());
        self::assertNotNull($log->createdAt());
        self::assertNull($log->updatedAt());
    }

    public function testCreateWithReference(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::EMPLOYEE,
            'emp-456',
            NotificationChannel::IN_APP,
            EventType::TICKET_ASSIGNED,
            'New Ticket',
            'You have a new ticket',
            'Ticket',
            'ticket-789',
        );

        self::assertSame('Ticket', $log->referenceType());
        self::assertSame('ticket-789', $log->referenceId());
    }

    public function testThrowsOnEmptyRecipientId(): void
    {
        $this->expectException(EmptyRecipientException::class);
        $this->expectExceptionMessage('El destinatario es obligatorio');

        NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            '',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );
    }

    public function testThrowsOnWhitespaceOnlyRecipientId(): void
    {
        $this->expectException(EmptyRecipientException::class);

        NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            '   ',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );
    }

    public function testThrowsOnEmptyBody(): void
    {
        $this->expectException(EmptyBodyException::class);
        $this->expectExceptionMessage('El cuerpo de la notificación es obligatorio');

        NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            '',
        );
    }

    public function testThrowsOnWhitespaceOnlyBody(): void
    {
        $this->expectException(EmptyBodyException::class);

        NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            '   ',
        );
    }

    public function testMarkAsReadForInApp(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::IN_APP,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        self::assertFalse($log->isRead());
        self::assertNull($log->readAt());

        $log->markAsRead();

        self::assertTrue($log->isRead());
        self::assertNotNull($log->readAt());
    }

    public function testThrowsWhenMarkAsReadNonInApp(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $this->expectException(CannotMarkReadNonInAppException::class);
        $this->expectExceptionMessage('Solo notificaciones IN_APP pueden marcarse como leídas');

        $log->markAsRead();
    }

    public function testMarkSent(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        self::assertSame(NotificationStatus::PENDING, $log->status());
        self::assertSame(0, $log->attemptCount());

        $log->markSent();

        self::assertSame(NotificationStatus::SENT, $log->status());
        self::assertSame(1, $log->attemptCount());
        self::assertNotNull($log->lastAttemptAt());
        self::assertNotNull($log->sentAt());
        self::assertNull($log->failureReason());
    }

    public function testMarkFailed(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markFailed('SMTP connection failed');

        self::assertSame(NotificationStatus::PENDING, $log->status()); // Not FAILED yet (attemptCount < 3)
        self::assertSame(1, $log->attemptCount());
        self::assertNotNull($log->lastAttemptAt());
        self::assertSame('SMTP connection failed', $log->failureReason());
        self::assertNull($log->sentAt());
    }

    public function testMarkFailedThreeTimesBecomesFailed(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markFailed('Attempt 1');
        self::assertSame(NotificationStatus::PENDING, $log->status());

        $log->markFailed('Attempt 2');
        self::assertSame(NotificationStatus::PENDING, $log->status());

        $log->markFailed('Attempt 3');
        self::assertSame(NotificationStatus::FAILED, $log->status());
    }

    public function testCannotMarkFailedIfAlreadySent(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markSent();

        $this->expectException(InvalidNotificationStatusTransitionException::class);
        $this->expectExceptionMessage('No se puede marcar como fallida una notificación ya enviada');

        $log->markFailed('Some reason');
    }

    public function testMarkFailedIdempotentWhenAlreadyFailed(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markFailed('Attempt 1');
        $log->markFailed('Attempt 2');
        $log->markFailed('Attempt 3');
        self::assertSame(NotificationStatus::FAILED, $log->status());
        $firstFailureReason = $log->failureReason();

        // Calling markFailed again when already FAILED should not change anything
        $log->markFailed('Attempt 4');
        self::assertSame(NotificationStatus::FAILED, $log->status());
        self::assertSame(3, $log->attemptCount()); // Doesn't increment
        self::assertSame($firstFailureReason, $log->failureReason()); // Doesn't change
    }

    public function testCanRetryWhenPendingAndAttemptsLessThanThree(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markFailed('Attempt 1');
        self::assertTrue($log->canRetry());

        $log->markFailed('Attempt 2');
        self::assertTrue($log->canRetry());
    }

    public function testCannotRetryWhenSent(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markSent();
        self::assertFalse($log->canRetry());
    }

    public function testCannotRetryWhenFailed(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markFailed('Attempt 1');
        $log->markFailed('Attempt 2');
        $log->markFailed('Attempt 3');
        self::assertFalse($log->canRetry());
    }

    public function testRetryResetsState(): void
    {
        $log = NotificationLog::create(
            $this->logId,
            RecipientType::USER,
            'user-123',
            NotificationChannel::EMAIL,
            EventType::USER_REGISTERED,
            'Subject',
            'Body',
        );

        $log->markFailed('Attempt 1');
        $log->markFailed('Attempt 2');
        self::assertSame(2, $log->attemptCount());
        self::assertSame('Attempt 2', $log->failureReason());
        self::assertSame(NotificationStatus::PENDING, $log->status());

        $log->retry();

        self::assertSame(NotificationStatus::PENDING, $log->status());
        self::assertSame(0, $log->attemptCount());
        self::assertNull($log->failureReason());
    }
}
