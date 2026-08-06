<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RequestPasswordReset;

use App\Identity\Application\Exception\EmailNotFoundException;
use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Entities\UserToken;
use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Events\PasswordResetRequested;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\ForgotPasswordEmailSenderInterface;
use App\Identity\Domain\Service\UserByEmailResolverInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RequestPasswordResetHandler
{
    public function __construct(
        private UserByEmailResolverInterface $userByEmailResolver,
        private UserRepositoryInterface $userRepository,
        private UserTokenRepositoryInterface $tokenRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
        private ForgotPasswordEmailSenderInterface $emailSender,
        private UserEmailResolverInterface $emailResolver,
        private AuthLogRepositoryInterface $authLogRepository,
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $userId = $this->userByEmailResolver->resolveByEmail($command->email)
            ?? throw EmailNotFoundException::withEmail($command->email);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw EmailNotFoundException::withEmail($command->email);
        }

        // Eliminar tokens existentes del mismo tipo para este usuario
        $this->tokenRepository->deleteByUserAndType($userId, UserTokenType::PASSWORD_RESET);

        // Generar nuevo token de restablecimiento
        $tokenId = new UserTokenIdValueObject($this->idGenerator->generate());
        ['entity' => $token, 'plainToken' => $plainToken] = UserToken::generate(
            $tokenId,
            $userId,
            UserTokenType::PASSWORD_RESET,
        );

        $this->tokenRepository->save($token);

        // Resolver el email del usuario y enviar el correo con el token
        $email = $this->emailResolver->resolveEmail($userId);
        if ($email !== null) {
            $this->emailSender->sendPasswordResetEmail(
                $email,
                (string) $user->username(),
                (string) $plainToken,
            );
        }

        // Registrar evento de auditoría
        $this->authLogRepository->append(
            AuthLog::record(
                AuthLogIdValueObject::fromString($this->idGenerator->generate()),
                $userId,
                AuthEvent::PASSWORD_RESET_REQUESTED,
                '0.0.0.0',
                'forgot-password',
            ),
        );

        $this->publisher->publish(new PasswordResetRequested(
            userId: (string) $userId,
            email: $command->email,
        ));
    }
}
