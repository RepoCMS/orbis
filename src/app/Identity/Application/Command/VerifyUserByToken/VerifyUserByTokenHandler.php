<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\VerifyUserByToken;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\PlainTokenValueObject;
use App\Identity\Domain\ValueObjects\TokenHashValueObject;

/**
 * Verifica la cuenta de un usuario usando el token enviado por email.
 * Busca el token por hash, valida que no haya expirado, activa al usuario
 * y elimina el token físicamente.
 */
final readonly class VerifyUserByTokenHandler
{
    public function __construct(
        private UserRepositoryInterface      $userRepository,
        private UserTokenRepositoryInterface $userTokenRepository,
    ) {
    }

    public function __invoke(VerifyUserByTokenCommand $command): void
    {
        $plainToken = new PlainTokenValueObject($command->plainToken);
        $tokenHash  = TokenHashValueObject::fromPlainToken($plainToken);

        // Buscar el token por hash en la BD (no exponemos el hash al exterior)
        $userToken = $this->userTokenRepository->findByHash((string) $tokenHash)
            ?? throw new TokenException('El enlace de verificación no es válido o ya fue utilizado.');

        if ($userToken->type() !== UserTokenType::ACTIVATION) {
            throw new TokenException('El tipo de token no corresponde a una activación.');
        }

        $userToken->consume($plainToken);

        $userId = $userToken->userId();
        $user   = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId((string) $userId);

        $user->activate();

        $this->userRepository->save($user);
        $this->userTokenRepository->delete($userToken->id());
    }
}
