<?php

declare(strict_types=1);

namespace App\Expense\Presentation\Http;

use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceCommand;
use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceHandler;
use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceCommand;
use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceHandler;
use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesHandler;
use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/expense/cash-advances')]
final class ApiExpenseCashAdvanceController extends AbstractController
{
    #[IsGranted('expense.view')]
    #[Route('', name: 'api_expense_ca_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListCashAdvancesHandler $handler,
    ): JsonResponse {
        $advances = $handler(new ListCashAdvancesQuery(
            employeeId: $request->query->get('employeeId') ?: null,
            status: $request->query->get('status') ?: null,
            daysOverdue: $request->query->get('daysOverdue') !== null
                ? (int) $request->query->get('daysOverdue')
                : null,
        ));

        return $this->json(['data' => $advances]);
    }

    #[IsGranted('cash_advance.create')]
    #[Route('', name: 'api_expense_ca_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateCashAdvanceHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            return $this->json(
                ['error' => 'JSON inválido: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $employeeId = isset($payload['employeeId']) ? trim((string) $payload['employeeId']) : '';
        $givenBy    = isset($payload['givenBy']) ? trim((string) $payload['givenBy']) : '';
        $amount     = $payload['amount'] ?? null;
        $givenAt    = isset($payload['givenAt']) ? trim((string) $payload['givenAt']) : '';

        if ($employeeId === '') {
            return $this->json(
                ['error' => 'El campo employeeId es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($givenBy === '') {
            return $this->json(
                ['error' => 'El campo givenBy es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($amount === null || !is_numeric($amount) || (float) $amount <= 0) {
            return $this->json(
                ['error' => 'El campo amount es obligatorio y debe ser un número positivo'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($givenAt === '') {
            return $this->json(
                ['error' => 'El campo givenAt es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $employeeId)) {
            return $this->json(
                ['error' => 'El employeeId no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $givenBy)) {
            return $this->json(
                ['error' => 'El givenBy no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $id = $handler(new CreateCashAdvanceCommand(
                employeeId: $employeeId,
                givenBy: $givenBy,
                amount: (float) $amount,
                givenAt: $givenAt,
            ));

            return $this->json([
                'data' => [
                    'id'     => $id,
                    'status' => 'outstanding',
                ],
            ], Response::HTTP_CREATED);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('cash_advance.settle')]
    #[Route('/{id}/settle', name: 'api_expense_ca_settle', methods: ['POST'])]
    public function settle(
        string $id,
        Request $request,
        SettleCashAdvanceHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            return $this->json(
                ['error' => 'JSON inválido: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $settlementConfirmedBy = isset($payload['settlementConfirmedBy'])
            ? trim((string) $payload['settlementConfirmedBy'])
            : '';
        $settlementDirection = isset($payload['settlementDirection'])
            ? trim((string) $payload['settlementDirection'])
            : '';

        if ($settlementConfirmedBy === '') {
            return $this->json(
                ['error' => 'El campo settlementConfirmedBy es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($settlementDirection === '') {
            return $this->json(
                ['error' => 'El campo settlementDirection es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $settlementConfirmedBy)) {
            return $this->json(
                ['error' => 'El settlementConfirmedBy no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $handler(new SettleCashAdvanceCommand(
                cashAdvanceId: $id,
                settlementConfirmedBy: $settlementConfirmedBy,
                settlementDirection: $settlementDirection,
            ));

            return $this->json([
                'data' => [
                    'id'                    => $id,
                    'status'                => 'settled',
                    'settlementConfirmedAt' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
                ],
            ]);
        } catch (\DomainException | \InvalidArgumentException | \ValueError $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
