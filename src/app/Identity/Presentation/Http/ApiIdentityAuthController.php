<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\Identity\Application\Command\RequestPasswordReset\RequestPasswordResetHandler;
use App\Identity\Application\Command\ResetPassword\ResetPasswordCommand;
use App\Identity\Application\Command\ResetPassword\ResetPasswordHandler;
use App\Identity\Application\Exception\EmailNotFoundException;
use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Identity\Infrastructure\Security\TotpService;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api/v1/auth')]
final class ApiIdentityAuthController extends AbstractController
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly UserMfaConfigRepositoryInterface $mfaConfigRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): JsonResponse
    {
        $payload = $request->toArray();
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->json(
                ['error' => 'Username y password son requeridos.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Delegar al login form de Symfony (session-based)
        // En una implementación futura con JWT, aquí se autenticaría contra el firewall
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error !== null) {
            return $this->json(
                ['error' => 'Credenciales inválidas.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $user = $this->getUser();

        if ($user === null) {
            return $this->json(
                ['error' => 'Credenciales inválidas.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        /** @var SymfonySecurityUser $secUser */
        $secUser = $user;
        $userId = (string) $secUser->getDomainUser()->id();

        $mfaConfig = $this->mfaConfigRepository->findByUserId(
            $secUser->getDomainUser()->id(),
        );

        if ($mfaConfig !== null && $mfaConfig->isEnabled()) {
            return $this->json([
                'mfaRequired' => true,
                'userId'      => $userId,
            ]);
        }

        return $this->json([
            'mfaRequired' => false,
            'userId'      => $userId,
        ]);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Symfony firewall maneja el logout; este endpoint existe para la API contract
        return $this->json(['data' => ['message' => 'Sesión cerrada.']]);
    }

    #[Route('/mfa/setup', name: 'api_auth_mfa_setup', methods: ['POST'])]
    public function mfaSetup(Request $request): JsonResponse
    {
        /** @var SymfonySecurityUser $secUser */
        $secUser  = $this->getUser();
        $userId   = $secUser->getDomainUser()->id();
        $username = (string) $secUser->getDomainUser()->username();

        $mfaConfig = $this->mfaConfigRepository->findByUserId($userId);

        if ($mfaConfig !== null && $mfaConfig->isEnabled()) {
            return $this->json(
                ['error' => 'MFA ya está configurado.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $secret = $request->getSession()->get('identity.2fa.pending_secret');
        if ($secret === null) {
            $secret = $this->totp->generateSecret();
            $request->getSession()->set('identity.2fa.pending_secret', $secret);
        }

        return $this->json([
            'data' => [
                'qrCodeSvg'  => $this->totp->getQrCodeDataUri($username, $secret),
                'secret'     => $secret,
            ],
        ]);
    }

    #[Route('/mfa/confirm', name: 'api_auth_mfa_confirm', methods: ['POST'])]
    public function mfaConfirm(Request $request): JsonResponse
    {
        /** @var SymfonySecurityUser $secUser */
        $secUser = $this->getUser();
        $userId  = $secUser->getDomainUser()->id();

        $payload = $request->toArray();
        $code    = trim((string) ($payload['totpCode'] ?? ''));
        $secret  = (string) $request->getSession()->get('identity.2fa.pending_secret', '');

        if ($secret === '') {
            return $this->json(
                ['error' => 'No hay una configuración MFA pendiente. Inicia /mfa/setup primero.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (!$this->totp->verify($secret, $code)) {
            return $this->json(
                ['error' => 'Código incorrecto. Verifica la hora de tu dispositivo.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $mfaConfig = $this->mfaConfigRepository->findByUserId($userId);

        if ($mfaConfig === null) {
            $mfaConfig = UserMfaConfig::setup(
                new UserMfaConfigIdValueObject($this->idGenerator->generate()),
                $userId,
                $secret,
            );
        } else {
            $mfaConfig->updateSecret($secret);
        }

        $mfaConfig->confirm();
        $this->mfaConfigRepository->save($mfaConfig);
        $request->getSession()->remove('identity.2fa.pending_secret');

        return $this->json(['data' => ['status' => 'MFA activado correctamente.']]);
    }

    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, RequestPasswordResetHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $email   = trim((string) ($payload['email'] ?? ''));

        if ($email === '') {
            return $this->json(
                ['error' => 'El email es requerido.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(
                ['error' => 'El formato del email no es válido.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            ($handler)(new RequestPasswordResetCommand($email));

            // Por seguridad, siempre devolver éxito aunque el email no exista
            return $this->json(['data' => ['message' => 'Si el email existe, recibirás un enlace para restablecer tu contraseña.']]);
        } catch (EmailNotFoundException) {
            return $this->json(['data' => ['message' => 'Si el email existe, recibirás un enlace para restablecer tu contraseña.']]);
        } catch (DomainException) {
            return $this->json(['data' => ['message' => 'Si el email existe, recibirás un enlace para restablecer tu contraseña.']]);
        }
    }

    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, ResetPasswordHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $token   = trim((string) ($payload['token'] ?? ''));
        $newPassword = (string) ($payload['newPassword'] ?? '');

        if ($token === '' || $newPassword === '') {
            return $this->json(
                ['error' => 'Token y nueva contraseña son requeridos.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            ($handler)(new ResetPasswordCommand($token, $newPassword));

            return $this->json(['data' => ['message' => 'Contraseña restablecida correctamente.']]);
        } catch (TokenException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
