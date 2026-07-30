<?php

declare(strict_types=1);

namespace App\Expense\Presentation\Http;

use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryHandler;
use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/expense/dashboard')]
final class ApiExpenseDashboardController extends AbstractController
{
    #[IsGranted('expense.view')]
    #[Route('/summary', name: 'api_expense_dashboard_summary', methods: ['GET'])]
    public function summary(
        GetExpenseDashboardSummaryHandler $handler,
    ): JsonResponse {
        $summary = $handler(new GetExpenseDashboardSummaryQuery());

        return $this->json(['data' => $summary]);
    }
}
