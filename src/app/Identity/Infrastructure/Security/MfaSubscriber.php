<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Intercepta cada request y redirige al flujo MFA si el usuario
 * está autenticado pero aún no ha completado el segundo factor.
 *
 * Rutas exentas: login, logout, 2fa/*, _profiler, assets.
 */
final class MfaSubscriber implements EventSubscriberInterface
{
    private const SESSION_KEY   = 'identity.2fa.verified';
    private const EXEMPT_ROUTES = [
        'app_login',
        'app_logout',
        'mfa_check',
        'mfa_setup',
        'mfa_setup_confirm',
        'mfa_backup_codes',
        'mfa_backup_codes_confirm',
        'mfa_recovery',
        'mfa_recovery_submit',
        'verification_verify',
    ];
    private const EXEMPT_PREFIXES = ['/_', '/css/', '/js/', '/images/'];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router,
        private readonly UserMfaConfigRepositoryInterface $mfaConfigRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 6]]; // antes del router (8) no, después (6)
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip MFA check in dev environment
        if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        // Eximir rutas de assets y profiler
        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        // Eximir por nombre de ruta
        $route = $request->attributes->get('_route');
        if ($route !== null && in_array($route, self::EXEMPT_ROUTES, true)) {
            return;
        }

        // Si no hay usuario autenticado, security.yaml ya gestiona el redirect
        $token = $this->tokenStorage->getToken();
        if ($token === null || !$token->getUser() instanceof SymfonySecurityUser) {
            return;
        }

        /** @var SymfonySecurityUser $secUser */
        $secUser  = $token->getUser();
        $session  = $request->getSession();
        $verified = (bool) $session->get(self::SESSION_KEY, false);

        if ($verified) {
            return;
        }

        // Decidir si el usuario necesita configurar o verificar 2FA
        $userId    = $secUser->getDomainUser()->id();
        $mfaConfig = $this->mfaConfigRepository->findByUserId($userId);
        $hasMfaEnabled = $mfaConfig !== null && $mfaConfig->isEnabled();
        $target = $hasMfaEnabled
            ? $this->router->generate('mfa_check')
            : $this->router->generate('mfa_setup');

        // Evitar bucle de redirección si ya estamos en la ruta de 2FA
        if ($path === $target) {
            return;
        }

        $event->setResponse(new RedirectResponse($target));
    }
}
