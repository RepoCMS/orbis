<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Identity\Domain\Service\AccountNotifierInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class SymfonyMailerAccountNotifier implements AccountNotifierInterface
{
    public function __construct(
        private readonly MailerInterface        $mailer,
        private readonly UrlGeneratorInterface  $urlGenerator,
        private readonly Environment            $twig,
        private readonly string                 $fromEmail,
        private readonly string                 $fromName,
    ) {
    }

    public function sendActivationEmail(string $toEmail, string $username, string $token): void
    {
        $activationUrl = $this->urlGenerator->generate(
            'account_verify',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $html = $this->twig->render('email/activation.html.twig', [
            'username'      => $username,
            'activationUrl' => $activationUrl,
            'expiresHours'  => 48,
        ]);

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($toEmail)
            ->subject('Activa tu cuenta en Orbis')
            ->html($html);

        $this->mailer->send($email);
    }
}
