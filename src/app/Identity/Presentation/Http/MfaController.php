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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/2fa')]
final class MfaController extends AbstractController
{
    private const SESSION_KEY = 'identity.2fa.verified';

    public function __construct(
        private readonly TotpService $totp,
        private readonly UserMfaConfigRepositoryInterface $mfaConfigRepository,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly LandingRouteResolver $landingRouteResolver,
    ) {
    }

    // ── Setup: usuario sin MFA configurado o confirmado ────────────────────────

    #[Route('/setup', name: 'mfa_setup', methods: ['GET'])]
    public function setup(Request $request): Response
    {
        /** @var SymfonySecurityUser $secUser */
        $secUser   = $this->getUser();
        $userId    = $secUser->getDomainUser()->id();
        $mfaConfig = $this->mfaConfigRepository->findByUserId($userId);

        // Si ya tiene MFA confirmado, ir al check
        if ($mfaConfig !== null && $mfaConfig->isEnabled()) {
            return $this->redirectToRoute('mfa_check');
        }

        // Generar secret temporal (guardado en sesión hasta confirmar)
        $secret = $request->getSession()->get('identity.2fa.pending_secret');
        if ($secret === null) {
            $secret = $this->totp->generateSecret();
            $request->getSession()->set('identity.2fa.pending_secret', $secret);
        }

        return $this->render('security/2fa_setup.html.twig', [
            'qr_data_uri' => $this->totp->getQrCodeDataUri(
                (string) $secUser->getDomainUser()->username(),
                $secret,
            ),
            'secret' => $secret,
        ]);
    }

    #[Route('/setup/confirm', name: 'mfa_setup_confirm', methods: ['POST'])]
    public function setupConfirm(Request $request): Response
    {
        /** @var SymfonySecurityUser $secUser */
        $secUser = $this->getUser();
        $userId  = $secUser->getDomainUser()->id();

        $secret = (string) $request->getSession()->get('identity.2fa.pending_secret', '');
        $code   = trim((string) $request->request->get('code', ''));

        if ($secret === '' || !$this->totp->verify($secret, $code)) {
            $this->addFlash('error', 'Código incorrecto. Verifica la hora de tu dispositivo e intenta de nuevo.');
            return $this->redirectToRoute('mfa_setup');
        }

        // Crear o actualizar UserMfaConfig y confirmar
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

        // Marcar 2FA como completado en sesión
        $request->getSession()->set(self::SESSION_KEY, true);

        $this->addFlash('success', 'Autenticación en dos pasos activada correctamente.');
        return $this->redirectToRoute($this->landingRouteResolver->resolveFor($secUser));
    }

    // ── Check: usuario con MFA configurado, verificar código ────────────────────

    #[Route('/check', name: 'mfa_check', methods: ['GET'])]
    public function check(): Response
    {
        return $this->render('security/2fa_check.html.twig');
    }

    #[Route('/check', name: 'mfa_check_submit', methods: ['POST'])]
    public function checkSubmit(Request $request): Response
    {
        /** @var SymfonySecurityUser $secUser */
        $secUser   = $this->getUser();
        $userId    = $secUser->getDomainUser()->id();
        $mfaConfig = $this->mfaConfigRepository->findByUserId($userId);

        $code = trim((string) $request->request->get('code', ''));

        if ($mfaConfig === null || !$mfaConfig->isEnabled() || !$this->totp->verify($mfaConfig->secret(), $code)) {
            return $this->render('security/2fa_check.html.twig', [
                'error' => 'Código incorrecto o expirado.',
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $request->getSession()->set(self::SESSION_KEY, true);
        return $this->redirectToRoute($this->landingRouteResolver->resolveFor($secUser));
    }
}
