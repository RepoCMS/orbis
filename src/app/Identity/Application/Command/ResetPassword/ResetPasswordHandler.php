<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ResetPassword;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Events\PasswordResetCompleted;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\PlainTokenValueObject;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ResetPasswordHandler
{
    public function __construct(
        private UserTokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private DomainEventPublisherInterface $publisher,
        private AuthLogRepositoryInterface $authLogRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(ResetPasswordCommand $command): void
    {
        $plainToken = new PlainTokenValueObject($command->token);

        // Buscar token por tipo PASSWORD_RESET — se busca por el hash del token
        // Usamos findByHash para encontrar el token; primero hay que resolverlo
        // a través del repositorio: buscamos el token activo no expirado

        // Buscar el token en todos los tokens PASSWORD_RESET
        $tokenHash = \App\Identity\Domain\ValueObjects\TokenHashValueObject::fromPlainToken($plainToken);

        // Nota: El repositorio ya expone findByHash que busca por token_hash
        // El token hash es único, por lo que podemos encontrarlo directamente
        $token = $this->tokenRepository->findByHash((string) $tokenHash);

        if ($token === null) {
            throw new \App\Identity\Domain\Exceptions\TokenException('El token no es válido o ha expirado.');
        }

        // Verificar que es un token de restablecimiento de contraseña
        if ($token->type() !== UserTokenType::PASSWORD_RESET) {
            throw new \App\Identity\Domain\Exceptions\TokenException('El token no es válido para esta operación.');
        }

        // Validar el token (lógica de dominio)
        $token->consume($plainToken);

        // Obtener el usuario
        $userId = $token->userId();
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId((string) $userId);

        // Cambiar la contraseña
        $rawPassword = new RawPasswordValueObject($command->newPassword);
        $hashedPassword = $this->passwordHasher->hash($rawPassword);
        $user->changePassword($hashedPassword);

        $this->userRepository->save($user);

        // Eliminar el token consumido
        $this->tokenRepository->delete($token->id());

        // Registrar evento de auditoría
        $this->authLogRepository->append(
            AuthLog::record(
                AuthLogIdValueObject::fromString($this->idGenerator->generate()),
                $userId,
                AuthEvent::PASSWORD_RESET_COMPLETED,
                '0.0.0.0',
                'reset-password',
            ),
        );

        $this->publisher->publish(new PasswordResetCompleted(
            userId: (string) $userId,
        ));
    }
}
