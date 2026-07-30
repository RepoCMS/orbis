<?php

declare(strict_types=1);

namespace App\Expense\Presentation\Http;

use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemCommand;
use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemHandler;
use App\Expense\Application\Command\ApproveExpenseItem\ApproveExpenseItemCommand;
use App\Expense\Application\Command\ApproveExpenseItem\ApproveExpenseItemHandler;
use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseCommand;
use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseHandler;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseCommand;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseHandler;
use App\Expense\Application\Command\RejectExpenseItem\RejectExpenseItemCommand;
use App\Expense\Application\Command\RejectExpenseItem\RejectExpenseItemHandler;
use App\Expense\Application\Command\ResubmitExpense\ResubmitExpenseCommand;
use App\Expense\Application\Command\ResubmitExpense\ResubmitExpenseHandler;
use App\Expense\Application\Command\SubmitExpense\SubmitExpenseCommand;
use App\Expense\Application\Command\SubmitExpense\SubmitExpenseHandler;
use App\Expense\Application\Command\UploadEvidence\UploadEvidenceCommand;
use App\Expense\Application\Command\UploadEvidence\UploadEvidenceHandler;
use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailHandler;
use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailQuery;
use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesHandler;
use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/expense/operating-expenses')]
final class ApiExpenseOperatingExpenseController extends AbstractController
{
    #[IsGranted('expense.view')]
    #[Route('', name: 'api_expense_oe_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListOperatingExpensesHandler $handler,
    ): JsonResponse {
        $expenses = $handler(new ListOperatingExpensesQuery(
            page: max(1, (int) $request->query->get('page', '1')),
            limit: max(1, min(100, (int) $request->query->get('limit', '20'))),
            employeeId: $request->query->get('employeeId') ?: null,
            status: $request->query->get('status') ?: null,
            dateFrom: $request->query->get('dateFrom') ?: null,
            dateTo: $request->query->get('dateTo') ?: null,
            ticketId: $request->query->get('ticketId') ?: null,
        ));

        return $this->json(['data' => $expenses]);
    }

    #[IsGranted('expense.create')]
    #[Route('', name: 'api_expense_oe_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateOperatingExpenseHandler $handler,
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
        $reason     = isset($payload['reason']) ? trim((string) $payload['reason']) : '';
        $startDate  = isset($payload['startDate']) ? trim((string) $payload['startDate']) : '';
        $endDate    = isset($payload['endDate']) ? trim((string) $payload['endDate']) : '';

        if ($employeeId === '') {
            return $this->json(
                ['error' => 'El campo employeeId es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($reason === '') {
            return $this->json(
                ['error' => 'El campo reason es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($startDate === '') {
            return $this->json(
                ['error' => 'El campo startDate es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($endDate === '') {
            return $this->json(
                ['error' => 'El campo endDate es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $employeeId)) {
            return $this->json(
                ['error' => 'El employeeId no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $id = $handler(new CreateOperatingExpenseCommand(
                employeeId: $employeeId,
                reason: $reason,
                startDate: $startDate,
                endDate: $endDate,
                cashAdvanceId: isset($payload['cashAdvanceId']) ? trim($payload['cashAdvanceId']) : null,
                ticketIds: $payload['ticketIds'] ?? [],
            ));

            return $this->json(['data' => ['id' => $id]], Response::HTTP_CREATED);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.view')]
    #[Route('/{id}', name: 'api_expense_oe_show', methods: ['GET'])]
    public function show(
        string $id,
        GetOperatingExpenseDetailHandler $handler,
    ): JsonResponse {
        $expense = $handler(new GetOperatingExpenseDetailQuery(expenseId: $id));

        if ($expense === null) {
            return $this->json(
                ['error' => 'Liquidación no encontrada'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json(['data' => $expense]);
    }

    #[IsGranted('expense.create')]
    #[Route('/{id}/items', name: 'api_expense_oe_add_item', methods: ['POST'])]
    public function addItem(
        string $id,
        Request $request,
        AddExpenseItemHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            return $this->json(
                ['error' => 'JSON inválido: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $type   = isset($payload['type']) ? trim((string) $payload['type']) : '';
        $amount = $payload['amount'] ?? null;

        if ($type === '') {
            return $this->json(
                ['error' => 'El campo type es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($amount === null || !is_numeric($amount)) {
            return $this->json(
                ['error' => 'El campo amount es obligatorio y debe ser numérico'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $itemId = $handler(new AddExpenseItemCommand(
                expenseId: $id,
                type: $type,
                amount: (float) $amount,
                ticketIds: $payload['ticketIds'] ?? [],
                notes: isset($payload['notes']) ? trim($payload['notes']) : null,
            ));

            return $this->json(
                ['data' => ['id' => $itemId, 'status' => 'pending']],
                Response::HTTP_CREATED
            );
        } catch (\DomainException | \InvalidArgumentException | \ValueError $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.submit')]
    #[Route('/{id}/submit', name: 'api_expense_oe_submit', methods: ['POST'])]
    public function submit(
        string $id,
        SubmitExpenseHandler $handler,
    ): JsonResponse {
        try {
            $handler(new SubmitExpenseCommand(expenseId: $id));

            return $this->json([
                'data' => [
                    'id'          => $id,
                    'status'      => 'submitted',
                    'submittedAt' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
                ],
            ]);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.approve')]
    #[Route('/{id}/approve', name: 'api_expense_oe_approve', methods: ['POST'])]
    public function approve(
        string $id,
        Request $request,
        ApproveOperatingExpenseHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            return $this->json(
                ['error' => 'JSON inválido: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $approvedBy = isset($payload['approvedBy']) ? trim((string) $payload['approvedBy']) : '';

        if ($approvedBy === '') {
            return $this->json(
                ['error' => 'El campo approvedBy es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $approvedBy)) {
            return $this->json(
                ['error' => 'El approvedBy no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $handler(new ApproveOperatingExpenseCommand(
                expenseId: $id,
                approvedBy: $approvedBy,
            ));

            return $this->json([
                'data' => [
                    'id'         => $id,
                    'status'     => 'approved',
                    'approvedBy' => $approvedBy,
                    'approvedAt' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
                ],
            ]);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.approve')]
    #[Route('/{id}/items/{itemId}/approve', name: 'api_expense_oe_approve_item', methods: ['POST'])]
    public function approveItem(
        string $id,
        string $itemId,
        Request $request,
        ApproveExpenseItemHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            return $this->json(
                ['error' => 'JSON inválido: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $reviewedBy = isset($payload['reviewedBy']) ? trim((string) $payload['reviewedBy']) : '';

        if ($reviewedBy === '') {
            return $this->json(
                ['error' => 'El campo reviewedBy es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $reviewedBy)) {
            return $this->json(
                ['error' => 'El reviewedBy no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $handler(new ApproveExpenseItemCommand(
                expenseId: $id,
                itemId: $itemId,
                reviewedBy: $reviewedBy,
            ));

            return $this->json([
                'data' => [
                    'id'         => $itemId,
                    'itemStatus' => 'approved',
                ],
            ]);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.approve')]
    #[Route('/{id}/items/{itemId}/reject', name: 'api_expense_oe_reject_item', methods: ['POST'])]
    public function rejectItem(
        string $id,
        string $itemId,
        Request $request,
        RejectExpenseItemHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            return $this->json(
                ['error' => 'JSON inválido: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $rejectionReason = isset($payload['rejectionReason'])
            ? trim((string) $payload['rejectionReason'])
            : '';
        $reviewedBy = isset($payload['reviewedBy']) ? trim((string) $payload['reviewedBy']) : '';

        if ($rejectionReason === '') {
            return $this->json(
                ['error' => 'El campo rejectionReason es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        if ($reviewedBy === '') {
            return $this->json(
                ['error' => 'El campo reviewedBy es obligatorio'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $reviewedBy)) {
            return $this->json(
                ['error' => 'El reviewedBy no tiene un formato ULID válido'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $handler(new RejectExpenseItemCommand(
                expenseId: $id,
                itemId: $itemId,
                rejectionReason: $rejectionReason,
                reviewedBy: $reviewedBy,
            ));

            return $this->json([
                'data' => [
                    'id'              => $itemId,
                    'itemStatus'      => 'rejected',
                    'expenseStatus'   => 'rejected',
                    'rejectionReason' => $rejectionReason,
                ],
            ]);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.submit')]
    #[Route('/{id}/resubmit', name: 'api_expense_oe_resubmit', methods: ['POST'])]
    public function resubmit(
        string $id,
        ResubmitExpenseHandler $handler,
    ): JsonResponse {
        try {
            $handler(new ResubmitExpenseCommand(expenseId: $id));

            return $this->json([
                'data' => [
                    'id'          => $id,
                    'status'      => 'submitted',
                    'submittedAt' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
                ],
            ]);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    #[IsGranted('expense.create')]
    #[Route('/{id}/items/{itemId}/evidence', name: 'api_expense_oe_upload_evidence', methods: ['POST'])]
    public function uploadEvidence(
        string $id,
        string $itemId,
        Request $request,
        UploadEvidenceHandler $handler,
    ): JsonResponse {
        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(
                ['error' => 'El archivo es obligatorio'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/expenses';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $filename = sprintf(
            '%s_%s_%s.%s',
            $id,
            $itemId,
            uniqid('', true),
            $file->guessExtension() ?? 'jpg'
        );
        $file->move($uploadsDir, $filename);
        $relativePath = 'uploads/expenses/' . $filename;

        try {
            $path = $handler(new UploadEvidenceCommand(
                expenseId: $id,
                itemId: $itemId,
                filePath: $relativePath,
            ));

            return $this->json(['data' => ['id' => $itemId, 'evidencePath' => $path]]);
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
