<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Channel;

use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Services\ChannelSenderInterface;
use App\Notification\Domain\Services\ContactResolverInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class EmailChannelSender implements ChannelSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
        private ContactResolverInterface $contactResolver,
    ) {}

    public function send(NotificationLog $log): void
    {
        $contact = $this->contactResolver->resolve(
            $log->recipientType()->value,
            $log->recipientId(),
        );

        if (empty($contact['email'])) {
            throw new \RuntimeException(sprintf(
                'No se pudo resolver el email del destinatario (recipientType: %s, recipientId: %s)',
                $log->recipientType()->value,
                $log->recipientId(),
            ));
        }

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($contact['email'])
            ->subject($log->subject() ?? 'Sin asunto')
            ->html($log->body());

        $this->mailer->send($email);
    }
}
