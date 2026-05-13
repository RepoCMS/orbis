<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\GenerateActivationToken;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\UserToken;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\AccountNotifierInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

/**
 * Genera (o regenera) un token de activación de cuenta y lo envía por email.
 * Si ya existe un token anterior del mismo tipo para el usuario, lo reemplaza.
 */
final readonly class GenerateActivationTokenHandler
{
    public function __construct(
        private UserRepositoryInterface      $userRepository,
        private UserTokenRepositoryInterface $userTokenRepository,
        private AccountNotifierInterface     $accountNotifier,
        private UserEmailResolverInterface   $emailResolver,
        private IdGeneratorInterface         $idGenerator,
    ) {
    }

    public function __invoke(GenerateActivationTokenCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user   = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $email = $this->emailResolver->resolveEmail($userId);
        if ($email === null) {
            throw new \RuntimeException('No se encontró un email asociado a este usuario (Employee o Contact).');
        }

        $this->userTokenRepository->deleteByUserAndType($userId, UserTokenType::ACTIVATION);

        ['entity' => $token, 'plainToken' => $plainToken] = UserToken::generate(
            new UserTokenIdValueObject($this->idGenerator->generate()),
            $userId,
            UserTokenType::ACTIVATION,
        );

        $this->userTokenRepository->save($token);

        $this->accountNotifier->sendActivationEmail(
            $email,
            (string) $user->username(),
            (string) $plainToken,
        );
    }
}
