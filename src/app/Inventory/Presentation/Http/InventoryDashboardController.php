<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Query\InventoryDashboardMetrics\InventoryDashboardMetricsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/dashboard')]
#[IsGranted('inventory.view')]
final class InventoryDashboardController extends AbstractController
{
    public function __construct(
        private readonly InventoryDashboardMetricsHandler $handler,
    ) {
    }

    #[Route('', name: 'inventory_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        $metrics = ($this->handler)();

        return $this->render('inventory/dashboard/index.html.twig', [
            'metrics' => $metrics,
        ]);
    }
}
