<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Command\RecordFailedLogin\RecordFailedLoginCommand;
use App\Identity\Application\Command\RecordFailedLogin\RecordFailedLoginHandler;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

final readonly class LoginFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RecordFailedLoginHandler $recordFailedLoginHandler,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginFailureEvent::class => 'onLoginFailure'];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $username = $request->request->get('username', '');

        if (!\is_string($username) || $username === '') {
            return;
        }

        try {
            $user = $this->userRepository->findByUsername(new UsernameValueObject($username));
        } catch (\Throwable) {
            return;
        }

        if ($user === null) {
            return;
        }

        ($this->recordFailedLoginHandler)(new RecordFailedLoginCommand(
            userId: (string) $user->id(),
        ));
    }
}
