<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Query\IdentityDashboardMetrics\IdentityDashboardMetricsHandler;
use App\Identity\Application\Query\IdentityDashboardMetrics\IdentityDashboardMetricsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/identity/dashboard', name: 'identity_dashboard_')]
#[IsGranted('identity.view_dashboard')]
final class IdentityDashboardController extends AbstractController
{
    public function __construct(
        private readonly IdentityDashboardMetricsHandler $handler,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function __invoke(): Response
    {
        $metrics = ($this->handler)(new IdentityDashboardMetricsQuery());

        return $this->render('identity/dashboard/index.html.twig', [
            'metrics' => $metrics,
        ]);
    }
}
