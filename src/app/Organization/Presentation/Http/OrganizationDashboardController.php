<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Organization\Application\Query\GetOrganizationDashboard\GetOrganizationDashboardHandler;
use App\Organization\Application\Query\GetOrganizationDashboard\GetOrganizationDashboardQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organization/dashboard', name: 'organization_dashboard_')]
#[IsGranted('organization.view_dashboard')]
final class OrganizationDashboardController extends AbstractController
{
    public function __construct(
        private readonly GetOrganizationDashboardHandler $handler,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function __invoke(): Response
    {
        $metrics = ($this->handler)(new GetOrganizationDashboardQuery());

        return $this->render('organization/dashboard/index.html.twig', [
            'metrics' => $metrics,
            'zoneNames' => $metrics['zoneNames'],
        ]);
    }
}
