<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\RecordFailedLogin\RecordFailedLoginCommand;
use App\Identity\Application\Command\RecordFailedLogin\RecordFailedLoginHandler;
use App\Identity\Application\Command\RevokeRefreshToken\RevokeRefreshTokenCommand;
use App\Identity\Application\Command\RevokeRefreshToken\RevokeRefreshTokenHandler;
use App\Identity\Application\Command\StoreRefreshToken\StoreRefreshTokenCommand;
use App\Identity\Application\Command\StoreRefreshToken\StoreRefreshTokenHandler;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Identity\Domain\Repositories\UserMfaBackupCodeRepositoryInterface;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Identity\Infrastructure\Security\SymfonyUserProvider;
use App\Identity\Infrastructure\Security\TotpService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[Route('/api/v1/auth/jwt')]
final class JwtApiController extends AbstractController
{
    private const int RATE_LIMIT_MAX_ATTEMPTS = 10;
    private const int RATE_LIMIT_MINUTES = 15;

    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TotpService $totp,
        private readonly UserMfaConfigRepositoryInterface $mfaConfigRepository,
        private readonly AuthLogRepositoryInterface $authLogRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly StoreRefreshTokenHandler $storeRefreshTokenHandler,
        private readonly RevokeRefreshTokenHandler $revokeRefreshTokenHandler,
        private readonly SymfonyUserProvider $userProvider,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RecordFailedLoginHandler $recordFailedLoginHandler,
        private readonly UserMfaBackupCodeRepositoryInterface $backupCodeRepo,
    ) {
    }

    #[Route('/login', name: 'api_jwt_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $ipAddress = $request->getClientIp() ?? 'unknown';
        $recentFailed = $this->authLogRepository->countRecentFailedByIp($ipAddress, self::RATE_LIMIT_MINUTES);

        if ($recentFailed >= self::RATE_LIMIT_MAX_ATTEMPTS) {
            return $this->json(
                ['error' => 'Demasiados intentos fallidos. Intenta de nuevo más tarde.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $payload = $request->toArray();
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $totpCode = trim((string) ($payload['totpCode'] ?? ''));
        $backupCode = trim((string) ($payload['backupCode'] ?? ''));

        if ($username === '' || $password === '') {
            return $this->json(
                ['error' => 'Username y password son requeridos.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            /** @var SymfonySecurityUser $symfonyUser */
            $symfonyUser = $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException) {
            return $this->json(
                ['error' => 'Credenciales inválidas.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (!$this->passwordHasher->isPasswordValid($symfonyUser, $password)) {
            ($this->recordFailedLoginHandler)(new RecordFailedLoginCommand(
                userId: (string) $symfonyUser->getDomainUser()->id(),
            ));

            return $this->json(
                ['error' => 'Credenciales inválidas.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $domainUser = $symfonyUser->getDomainUser();
        $userId = $domainUser->id();

        $domainUser->recordSuccessfulLogin();
        $this->userRepository->save($domainUser);

        $mfaConfig = $this->mfaConfigRepository->findByUserId($userId);

        if ($mfaConfig !== null && $mfaConfig->isEnabled()) {
            $mfaVerified = false;

            if ($totpCode !== '' && $this->totp->verify($mfaConfig->secret(), $totpCode)) {
                $mfaVerified = true;
            }

            if (!$mfaVerified && $backupCode !== '') {
                $backupCodeEntity = $this->backupCodeRepo->findByCode($userId, $backupCode);
                if ($backupCodeEntity !== null) {
                    $backupCodeEntity->markAsUsed();
                    $this->backupCodeRepo->save($backupCodeEntity);
                    $mfaVerified = true;
                }
            }

            if (!$mfaVerified) {
                $requiresCode = $totpCode === '' && $backupCode === '';

                return $this->json(
                    $requiresCode
                        ? ['error' => 'Código de verificación requerido.', 'mfaRequired' => true]
                        : ['error' => 'Código de verificación inválido.'],
                    Response::HTTP_UNAUTHORIZED,
                );
            }
        }

        $accessToken = $this->jwtManager->create($symfonyUser);

        $plainRefreshToken = bin2hex(random_bytes(32));
        ($this->storeRefreshTokenHandler)(new StoreRefreshTokenCommand(
            userId: (string) $userId,
            plainToken: $plainRefreshToken,
        ));

        return $this->json([
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $plainRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ],
        ]);
    }

    #[Route('/refresh', name: 'api_jwt_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $refreshToken = trim((string) ($payload['refresh_token'] ?? ''));

        if ($refreshToken === '') {
            return $this->json(
                ['error' => 'Refresh token es requerido.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $tokenHash = hash('sha256', $refreshToken);
        $storedToken = $this->refreshTokenRepository->findByHash($tokenHash);

        if ($storedToken === null || !$storedToken->isValid()) {
            return $this->json(
                ['error' => 'Refresh token inválido o expirado.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        ($this->revokeRefreshTokenHandler)(new RevokeRefreshTokenCommand(tokenHash: $tokenHash));

        $userId = (string) $storedToken->userId();
        $domainUser = $this->userRepository->findById(new UserIdValueObject($userId));

        if ($domainUser === null) {
            return $this->json(
                ['error' => 'Usuario no encontrado.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        /** @var SymfonySecurityUser $symfonyUser */
        $symfonyUser = $this->userProvider->loadUserByIdentifier((string) $domainUser->username());

        $accessToken = $this->jwtManager->create($symfonyUser);

        $plainRefreshToken = bin2hex(random_bytes(32));
        ($this->storeRefreshTokenHandler)(new StoreRefreshTokenCommand(
            userId: $userId,
            plainToken: $plainRefreshToken,
        ));

        return $this->json([
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $plainRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ],
        ]);
    }
}
