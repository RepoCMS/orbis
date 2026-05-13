<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Identity\Infrastructure\Security\LandingRouteResolver;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(private readonly LandingRouteResolver $landingRouteResolver)
    {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(): Response
    {
        /** @var SymfonySecurityUser|null $user */
        $user = $this->getUser();

        return $this->redirectToRoute($this->landingRouteResolver->resolveFor($user));
    }
}