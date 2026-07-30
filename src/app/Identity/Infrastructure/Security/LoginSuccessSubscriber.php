<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final readonly class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $symfonyUser = $event->getUser();

        if (!$symfonyUser instanceof SymfonySecurityUser) {
            return;
        }

        $domainUser = $symfonyUser->getDomainUser();
        $domainUser->recordSuccessfulLogin();

        $this->userRepository->save($domainUser);
    }
}
