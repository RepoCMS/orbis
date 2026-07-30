<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Identity\Infrastructure\Security\LandingRouteResolver;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Identity\Infrastructure\Security\TotpService;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/api/v1/identity/mfa')]
final class ApiIdentityMfaController extends AbstractController
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly UserMfaConfigRepositoryInterface $mfaConfigRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    #[Route('/setup', name: 'api_identity_mfa_setup', methods: ['POST'])]
    public function setup(Request $request): JsonResponse
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

    #[Route('/confirm', name: 'api_identity_mfa_confirm', methods: ['POST'])]
    public function confirm(Request $request): JsonResponse
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
}
