<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Infrastructure\Security\LandingRouteResolver;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly LandingRouteResolver $landingRouteResolver,
    ) {
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        /** @var SymfonySecurityUser|null $user */
        $user = $this->getUser();

        if ($user !== null) {
            return $this->redirectToRoute(
                $this->landingRouteResolver->resolveFor($user),
            );
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Este método nunca se ejecuta; el firewall de Symfony intercepta la ruta.
    }
}
