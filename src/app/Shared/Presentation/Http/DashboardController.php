<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Shared\Application\Query\Dashboard\DashboardMetricsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(DashboardMetricsHandler $handler): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'metrics' => $handler(),
        ]);
    }
}
