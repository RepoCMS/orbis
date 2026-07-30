<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure\EventListener;

use App\Notification\Application\Command\SendNotification\SendNotificationMessage;
use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\NotificationStatus;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Domain\Services\ContactResolverInterface;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use App\Notification\Infrastructure\Channel\EmailChannelSender;
use App\Notification\Infrastructure\Channel\InAppChannelSender;
use App\Notification\Infrastructure\Channel\SmsChannelSender;
use App\Notification\Infrastructure\Channel\WhatsAppChannelSender;
use App\Notification\Infrastructure\EventListener\SendNotificationMessageHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Tests for SendNotificationMessageHandler (async message handler).
 *
 * Fase 5: Event Subscriber Tests - Async message handler
 * Fase 7: P2 Mejoras - Backoff behavior verification
 */
final class SendNotificationMessageHandlerTest extends TestCase
{
    private const LOG_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createLog(
        NotificationChannel $channel = NotificationChannel::IN_APP,
    ): NotificationLog {
        return NotificationLog::create(
            id: new NotificationLogIdValueObject(self::LOG_ID),
            recipientType: RecipientType::EMPLOYEE,
            recipientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            channel: $channel,
            eventType: EventType::TICKET_CREATED,
            subject: 'Ticket creado',
            body: 'Se ha creado un nuevo ticket de soporte',
            referenceType: 'ticket',
            referenceId: 'TKT-001',
        );
    }

    private function createContactResolver(array $data = []): ContactResolverInterface
    {
        $resolver = $this->createMock(ContactResolverInterface::class);
        $resolver->method('resolve')
            ->willReturn(array_merge([
                'email' => 'test@example.com',
                'phone' => '+593999999999',
                'name' => 'Test User',
            ], $data));

        return $resolver;
    }

    private function createSmsSender(): SmsChannelSender
    {
        return new SmsChannelSender($this->createStub(\Psr\Log\LoggerInterface::class));
    }

    private function createWhatsAppSender(): WhatsAppChannelSender
    {
        return new WhatsAppChannelSender($this->createStub(\Psr\Log\LoggerInterface::class));
    }

    private function createEmailSender(
        ?MailerInterface $mailer = null,
        ?ContactResolverInterface $contactResolver = null,
    ): EmailChannelSender {
        return new EmailChannelSender(
            $mailer ?? $this->createStub(MailerInterface::class),
            'no-reply@orbis.test',
            $contactResolver ?? $this->createContactResolver(),
        );
    }

    // ─── Success scenarios ─────────────────────────────────────────

    public function testHandlerSendsInAppNotification(): void
    {
        $log = $this->createLog(NotificationChannel::IN_APP);

        $inAppSender = new InAppChannelSender();

        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);
        $repository->expects($this->once())
            ->method('save')
            ->with($log);

        $handler = new SendNotificationMessageHandler(
            $repository,
            $inAppSender,
            $this->createEmailSender(),
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        self::assertSame(NotificationStatus::SENT, $log->status());
        self::assertNotNull($log->sentAt());
    }

    public function testHandlerSendsEmailNotification(): void
    {
        $log = $this->createLog(NotificationChannel::EMAIL);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send');

        $contactResolver = $this->createContactResolver();
        $emailSender = $this->createEmailSender($mailer, $contactResolver);

        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);
        $repository->expects($this->once())
            ->method('save');

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $emailSender,
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        self::assertSame(NotificationStatus::SENT, $log->status());
    }

    public function testHandlerSendsSmsNotification(): void
    {
        $log = $this->createLog(NotificationChannel::SMS);

        // SMS sender just logs - just verify no error
        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $this->createEmailSender(),
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        self::assertSame(NotificationStatus::SENT, $log->status());
    }

    public function testHandlerSendsWhatsAppNotification(): void
    {
        $log = $this->createLog(NotificationChannel::WHATSAPP);

        // WhatsApp sender just logs
        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $this->createEmailSender(),
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        self::assertSame(NotificationStatus::SENT, $log->status());
    }

    // ─── Error scenarios ───────────────────────────────────────────

    public function testHandlerDoesNothingWhenLogNotFound(): void
    {
        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn(null);
        $repository->expects($this->never())
            ->method('save');

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $this->createEmailSender(),
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));
        // No assertion needed - we just verify no error, and save was never called
    }

    public function testHandlerDoesNothingWhenLogCannotRetry(): void
    {
        $log = $this->createLog(NotificationChannel::IN_APP);
        $log->markSent(); // SENT status

        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);
        $repository->expects($this->never())
            ->method('save');

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $this->createEmailSender(),
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));
    }

    public function testHandlerMarksFailedWhenSenderThrows(): void
    {
        $log = $this->createLog(NotificationChannel::EMAIL);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('SMTP connection failed'));

        $contactResolver = $this->createContactResolver();
        $emailSender = new EmailChannelSender($mailer, 'no-reply@orbis.test', $contactResolver);

        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);
        $repository->expects($this->once())
            ->method('save');

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $emailSender,
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        // First attempt, stays PENDING with 1 attempt
        self::assertSame(NotificationStatus::PENDING, $log->status());
        self::assertSame(1, $log->attemptCount());
        self::assertSame('SMTP connection failed', $log->failureReason());
    }

    // ─── Backoff / Retry behavior (Fase 7) ────────────────────────

    public function testHandlerMarksFailedWhenNoRecipientEmail(): void
    {
        $log = $this->createLog(NotificationChannel::EMAIL);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');

        $contactResolver = $this->createContactResolver(['email' => null]);
        $emailSender = new EmailChannelSender($mailer, 'no-reply@orbis.test', $contactResolver);

        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $emailSender,
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        self::assertNotNull($log->failureReason());
        self::assertStringContainsString('No se pudo resolver el email', $log->failureReason() ?? '');
    }

    public function testHandlerIncrementsAttemptCountOnFailure(): void
    {
        $log = $this->createLog(NotificationChannel::EMAIL);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')
            ->willThrowException(new \RuntimeException('Error'));

        $contactResolver = $this->createContactResolver();
        $emailSender = new EmailChannelSender($mailer, 'no-reply@orbis.test', $contactResolver);

        $repository = $this->createMock(NotificationLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($log);

        $handler = new SendNotificationMessageHandler(
            $repository,
            new InAppChannelSender(),
            $emailSender,
            $this->createSmsSender(),
            $this->createWhatsAppSender(),
        );

        ($handler)(new SendNotificationMessage(self::LOG_ID));

        self::assertSame(1, $log->attemptCount());
        self::assertNotNull($log->lastAttemptAt());
    }
}
