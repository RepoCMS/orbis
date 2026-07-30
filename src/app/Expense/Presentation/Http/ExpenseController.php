<?php

declare(strict_types=1);

namespace App\Expense\Presentation\Http;

use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemCommand;
use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemHandler;
use App\Expense\Application\Command\ApproveExpenseItem\ApproveExpenseItemCommand;
use App\Expense\Application\Command\ApproveExpenseItem\ApproveExpenseItemHandler;
use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseCommand;
use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseHandler;
use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceCommand;
use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceHandler;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseCommand;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseHandler;
use App\Expense\Application\Command\DeleteOperatingExpense\DeleteOperatingExpenseCommand;
use App\Expense\Application\Command\DeleteOperatingExpense\DeleteOperatingExpenseHandler;
use App\Expense\Application\Command\UpdateOperatingExpense\UpdateOperatingExpenseCommand;
use App\Expense\Application\Command\UpdateOperatingExpense\UpdateOperatingExpenseHandler;
use App\Expense\Application\Command\RejectExpenseItem\RejectExpenseItemCommand;
use App\Expense\Application\Command\RejectExpenseItem\RejectExpenseItemHandler;
use App\Expense\Application\Command\ResubmitExpense\ResubmitExpenseCommand;
use App\Expense\Application\Command\ResubmitExpense\ResubmitExpenseHandler;
use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceCommand;
use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceHandler;
use App\Expense\Application\Command\SubmitExpense\SubmitExpenseCommand;
use App\Expense\Application\Command\SubmitExpense\SubmitExpenseHandler;
use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryHandler;
use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryQuery;
use App\Expense\Application\Command\UpdateCashAdvance\UpdateCashAdvanceCommand;
use App\Expense\Application\Command\UpdateCashAdvance\UpdateCashAdvanceHandler;
use App\Expense\Application\Exception\CashAdvanceNotFoundException;
use App\Expense\Application\Query\GetCashAdvanceDetail\GetCashAdvanceDetailHandler;
use App\Expense\Application\Query\GetCashAdvanceDetail\GetCashAdvanceDetailQuery;
use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailHandler;
use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailQuery;
use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesHandler;
use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesQuery;
use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesHandler;
use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesQuery;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/expenses')]
final class ExpenseController extends AbstractController
{
    // ── Dashboard ──────────────────────────────────────────────────────────

    #[IsGranted('expense.view')]
    #[Route('/dashboard', name: 'expense_dashboard', methods: ['GET'])]
    public function dashboard(
        GetExpenseDashboardSummaryHandler $handler,
        ListOperatingExpensesHandler $listExpenses,
    ): Response {
        $summary = $handler(new GetExpenseDashboardSummaryQuery());

        $latest = $listExpenses(new ListOperatingExpensesQuery(page: 1, limit: 10));

        return $this->render('expense/dashboard/index.html.twig', [
            'summary' => $summary,
            'latestExpenses' => $latest,
        ]);
    }

    // ── Operating Expenses ─────────────────────────────────────────────────

    #[IsGranted('expense.view')]
    #[Route('/operating-expenses', name: 'expense_oe_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListOperatingExpensesHandler $handler,
        EmployeeRepositoryInterface $employeeRepo,
    ): Response {
        $expenses = $handler(new ListOperatingExpensesQuery(
            page: max(1, (int) $request->query->get('page', '1')),
            limit: 25,
            employeeId: $request->query->get('employeeId') ?: null,
            status: $request->query->get('status') ?: null,
            dateFrom: $request->query->get('dateFrom') ?: null,
            dateTo: $request->query->get('dateTo') ?: null,
        ));

        $employeeIds = [];
        foreach ($expenses as $e) {
            $employeeIds[] = $e->employeeId;
        }
        $employeeNames = [];
        foreach (array_unique($employeeIds) as $eid) {
            $emp = $employeeRepo->findById(new EmployeeIdValueObject($eid));
            $employeeNames[$eid] = $emp !== null ? $emp->firstName() . ' ' . $emp->lastName() : $eid;
        }

        return $this->render('expense/operating_expense/list.html.twig', [
            'expenses' => $expenses,
            'statuses' => ['draft', 'submitted', 'approved', 'rejected'],
            'employeeNames' => $employeeNames,
        ]);
    }

    #[IsGranted('expense.view')]
    #[Route('/operating-expenses/{id}', name: 'expense_oe_show', methods: ['GET'])]
    public function show(
        string $id,
        GetOperatingExpenseDetailHandler $handler,
        EmployeeRepositoryInterface $employeeRepo,
    ): Response {
        $expense = $handler(new GetOperatingExpenseDetailQuery(expenseId: $id));

        if ($expense === null) {
            throw $this->createNotFoundException('Liquidación no encontrada.');
        }

        $employeeName = $expense->employeeId;
        $emp = $employeeRepo->findById(new EmployeeIdValueObject($expense->employeeId));
        if ($emp !== null) {
            $employeeName = $emp->firstName() . ' ' . $emp->lastName();
        }

        return $this->render('expense/operating_expense/show.html.twig', [
            'expense' => $expense,
            'itemTypes' => ExpenseItemType::cases(),
            'employeeName' => $employeeName,
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/operating-expenses/new', name: 'expense_oe_new', methods: ['GET'])]
    public function newForm(
        ListEmployeesHandler $employees,
    ): Response {
        $allEmployees = $employees(new ListEmployeesQuery(limit: 500));

        return $this->render('expense/operating_expense/form.html.twig', [
            'errors' => [],
            'input' => [],
            'employees' => $allEmployees,
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/operating-expenses', name: 'expense_oe_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateOperatingExpenseHandler $handler,
        ListEmployeesHandler $employees,
    ): Response {
        $input = $request->request->all();

        try {
            $id = $handler(new CreateOperatingExpenseCommand(
                employeeId: trim($input['employeeId'] ?? ''),
                reason: trim($input['reason'] ?? ''),
                startDate: trim($input['startDate'] ?? ''),
                endDate: trim($input['endDate'] ?? ''),
                cashAdvanceId: !empty($input['cashAdvanceId']) ? trim($input['cashAdvanceId']) : null,
                ticketIds: $input['ticketIds'] ?? [],
            ));

            $this->addFlash('success', 'Liquidación creada correctamente.');
            return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
        } catch (DomainException | \InvalidArgumentException $e) {
            $allEmployees = $employees(new ListEmployeesQuery(limit: 500));

            return $this->render('expense/operating_expense/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input' => $input,
                'employees' => $allEmployees,
            ], new Response(null, 422));
        }
    }

    #[IsGranted('expense.submit')]
    #[Route('/operating-expenses/{id}/submit', name: 'expense_oe_submit', methods: ['POST'])]
    public function submit(string $id, SubmitExpenseHandler $handler): Response
    {
        try {
            $handler(new SubmitExpenseCommand(expenseId: $id));
            $this->addFlash('success', 'Liquidación enviada a revisión.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
    }

    #[IsGranted('expense.approve')]
    #[Route('/operating-expenses/{id}/approve', name: 'expense_oe_approve', methods: ['POST'])]
    public function approve(string $id, ApproveOperatingExpenseHandler $handler): Response
    {
        try {
            $handler(new ApproveOperatingExpenseCommand(
                expenseId: $id,
                approvedBy: $this->getUser()->getUserIdentifier(),
            ));
            $this->addFlash('success', 'Liquidación aprobada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
    }

    #[IsGranted('expense.submit')]
    #[Route('/operating-expenses/{id}/resubmit', name: 'expense_oe_resubmit', methods: ['POST'])]
    public function resubmit(string $id, ResubmitExpenseHandler $handler): Response
    {
        try {
            $handler(new ResubmitExpenseCommand(expenseId: $id));
            $this->addFlash('success', 'Liquidación reenviada a revisión.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
    }

    #[IsGranted('expense.create')]
    #[Route('/operating-expenses/{id}/edit', name: 'expense_oe_edit', methods: ['GET'])]
    public function editForm(
        string $id,
        GetOperatingExpenseDetailHandler $handler,
    ): Response {
        $expense = $handler(new GetOperatingExpenseDetailQuery(expenseId: $id));

        if ($expense === null) {
            throw $this->createNotFoundException('Liquidación no encontrada.');
        }

        return $this->render('expense/operating_expense/edit.html.twig', [
            'expense' => $expense,
            'errors' => [],
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/operating-expenses/{id}/edit', name: 'expense_oe_update', methods: ['POST'])]
    public function update(
        string $id,
        Request $request,
        UpdateOperatingExpenseHandler $handler,
        GetOperatingExpenseDetailHandler $detailHandler,
    ): Response {
        $input = $request->request->all();

        try {
            $handler(new UpdateOperatingExpenseCommand(
                expenseId: $id,
                reason: trim($input['reason'] ?? ''),
                startDate: trim($input['startDate'] ?? ''),
                endDate: trim($input['endDate'] ?? ''),
            ));

            $this->addFlash('success', 'Liquidación actualizada correctamente.');
            return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
        } catch (DomainException | \InvalidArgumentException $e) {
            $expense = $detailHandler(new GetOperatingExpenseDetailQuery(expenseId: $id));

            return $this->render('expense/operating_expense/edit.html.twig', [
                'expense' => $expense,
                'errors' => [$e->getMessage()],
            ], new Response(null, 422));
        }
    }

    #[IsGranted('expense.create')]
    #[Route('/operating-expenses/{id}/delete', name: 'expense_oe_delete', methods: ['POST'])]
    public function delete(
        string $id,
        DeleteOperatingExpenseHandler $handler,
    ): Response {
        try {
            $handler(new DeleteOperatingExpenseCommand(expenseId: $id));
            $this->addFlash('success', 'Liquidación eliminada correctamente.');
        } catch (DomainException | \InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('expense_oe_list');
    }

    #[IsGranted('expense.create')]
    #[Route('/operating-expenses/{id}/items', name: 'expense_oe_add_item', methods: ['POST'])]
    public function addItem(
        string $id,
        Request $request,
        AddExpenseItemHandler $handler,
    ): Response {
        $input = $request->request->all();

        try {
            $handler(new AddExpenseItemCommand(
                expenseId: $id,
                type: trim($input['type'] ?? ''),
                amount: (float) ($input['amount'] ?? 0),
                ticketIds: $input['ticketIds'] ?? [],
                notes: !empty($input['notes']) ? trim($input['notes']) : null,
            ));
            $this->addFlash('success', 'Item agregado.');
        } catch (DomainException | \InvalidArgumentException | \ValueError $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
    }

    #[IsGranted('expense.approve')]
    #[Route('/operating-expenses/{id}/items/{itemId}/approve', name: 'expense_oe_approve_item', methods: ['POST'])]
    public function approveItem(
        string $id,
        string $itemId,
        ApproveExpenseItemHandler $handler,
    ): Response {
        try {
            $handler(new ApproveExpenseItemCommand(
                expenseId: $id,
                itemId: $itemId,
                reviewedBy: $this->getUser()->getUserIdentifier(),
            ));
            $this->addFlash('success', 'Item aprobado.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
    }

    #[IsGranted('expense.approve')]
    #[Route('/operating-expenses/{id}/items/{itemId}/reject', name: 'expense_oe_reject_item', methods: ['POST'])]
    public function rejectItem(
        string $id,
        string $itemId,
        Request $request,
        RejectExpenseItemHandler $handler,
    ): Response {
        $reason = trim($request->request->get('rejectionReason', ''));

        if ($reason === '') {
            $this->addFlash('error', 'Debe indicar el motivo del rechazo.');
            return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
        }

        try {
            $handler(new RejectExpenseItemCommand(
                expenseId: $id,
                itemId: $itemId,
                rejectionReason: $reason,
                reviewedBy: $this->getUser()->getUserIdentifier(),
            ));
            $this->addFlash('success', 'Item rechazado.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_oe_show', ['id' => $id]);
    }

    // ── Cash Advances ────────────────────────────────────────────────────

    #[IsGranted('expense.view')]
    #[Route('/cash-advances', name: 'expense_ca_list', methods: ['GET'])]
    public function listAdvances(
        ListCashAdvancesHandler $handler,
        EmployeeRepositoryInterface $employeeRepo,
    ): Response {
        $advances = $handler(new ListCashAdvancesQuery());

        $employeeIds = [];
        foreach ($advances as $a) {
            $employeeIds[] = $a->employeeId;
        }
        $employeeNames = [];
        foreach (array_unique($employeeIds) as $eid) {
            $emp = $employeeRepo->findById(new EmployeeIdValueObject($eid));
            $employeeNames[$eid] = $emp !== null ? $emp->firstName() . ' ' . $emp->lastName() : $eid;
        }

        return $this->render('expense/cash_advance/list.html.twig', [
            'advances' => $advances,
            'employeeNames' => $employeeNames,
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/cash-advances/new', name: 'expense_ca_new', methods: ['GET'])]
    public function newAdvance(
        ListEmployeesHandler $employees,
    ): Response {
        $allEmployees = $employees(new ListEmployeesQuery(limit: 500));

        return $this->render('expense/cash_advance/form.html.twig', [
            'errors' => [],
            'input' => [],
            'employees' => $allEmployees,
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/cash-advances', name: 'expense_ca_create', methods: ['POST'])]
    public function createAdvance(
        Request $request,
        CreateCashAdvanceHandler $handler,
        ListEmployeesHandler $employees,
    ): Response {
        $input = $request->request->all();

        try {
            $id = $handler(new CreateCashAdvanceCommand(
                employeeId: trim($input['employeeId'] ?? ''),
                givenBy: $this->getUser()->getUserIdentifier(),
                amount: (float) ($input['amount'] ?? 0),
                givenAt: trim($input['givenAt'] ?? date('Y-m-d')),
            ));

            $this->addFlash('success', 'Anticipo creado correctamente.');
            return $this->redirectToRoute('expense_ca_list');
        } catch (DomainException | \InvalidArgumentException $e) {
            $allEmployees = $employees(new ListEmployeesQuery(limit: 500));

            return $this->render('expense/cash_advance/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input' => $input,
                'employees' => $allEmployees,
            ], new Response(null, 422));
        }
    }

    #[IsGranted('expense.view')]
    #[Route('/cash-advances/{id}/settle', name: 'expense_ca_settle', methods: ['POST'])]
    public function settleAdvance(
        string $id,
        Request $request,
        SettleCashAdvanceHandler $handler,
    ): Response {
        $direction = $request->request->get('direction', 'tech_to_company');

        try {
            $handler(new SettleCashAdvanceCommand(
                cashAdvanceId: $id,
                direction: $direction,
                confirmedBy: $this->getUser()->getUserIdentifier(),
            ));
            $this->addFlash('success', 'Anticipo liquidado correctamente.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('expense_ca_list');
    }

    #[IsGranted('expense.view')]
    #[Route('/cash-advances/{id}', name: 'expense_ca_show', methods: ['GET'])]
    public function showAdvance(
        string $id,
        GetCashAdvanceDetailHandler $handler,
        EmployeeRepositoryInterface $employeeRepo,
    ): Response {
        $advance = $handler(new GetCashAdvanceDetailQuery(cashAdvanceId: $id));

        if ($advance === null) {
            throw $this->createNotFoundException('Anticipo no encontrado.');
        }

        $employeeName = $advance->employeeId;
        $emp = $employeeRepo->findById(new EmployeeIdValueObject($advance->employeeId));
        if ($emp !== null) {
            $employeeName = $emp->firstName() . ' ' . $emp->lastName();
        }

        $givenByName = $advance->givenBy;
        $giver = $employeeRepo->findById(new EmployeeIdValueObject($advance->givenBy));
        if ($giver !== null) {
            $givenByName = $giver->firstName() . ' ' . $giver->lastName();
        }

        return $this->render('expense/cash_advance/show.html.twig', [
            'advance' => $advance,
            'employeeName' => $employeeName,
            'givenByName' => $givenByName,
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/cash-advances/{id}/edit', name: 'expense_ca_edit', methods: ['GET'])]
    public function editAdvance(
        string $id,
        GetCashAdvanceDetailHandler $handler,
        EmployeeRepositoryInterface $employeeRepo,
    ): Response {
        $advance = $handler(new GetCashAdvanceDetailQuery(cashAdvanceId: $id));

        if ($advance === null) {
            throw $this->createNotFoundException('Anticipo no encontrado.');
        }

        $employeeName = $advance->employeeId;
        $emp = $employeeRepo->findById(new EmployeeIdValueObject($advance->employeeId));
        if ($emp !== null) {
            $employeeName = $emp->firstName() . ' ' . $emp->lastName();
        }

        $givenByName = $advance->givenBy;
        $giver = $employeeRepo->findById(new EmployeeIdValueObject($advance->givenBy));
        if ($giver !== null) {
            $givenByName = $giver->firstName() . ' ' . $giver->lastName();
        }

        return $this->render('expense/cash_advance/edit.html.twig', [
            'advance' => $advance,
            'errors' => [],
            'employeeName' => $employeeName,
            'givenByName' => $givenByName,
        ]);
    }

    #[IsGranted('expense.create')]
    #[Route('/cash-advances/{id}/edit', name: 'expense_ca_update', methods: ['POST'])]
    public function updateAdvance(
        string $id,
        Request $request,
        UpdateCashAdvanceHandler $handler,
        GetCashAdvanceDetailHandler $detailHandler,
    ): Response {
        try {
            $handler(new UpdateCashAdvanceCommand(
                cashAdvanceId: $id,
                newAmount: (float) ($request->request->get('amount', 0)),
            ));

            $this->addFlash('success', 'Anticipo actualizado correctamente.');
            return $this->redirectToRoute('expense_ca_show', ['id' => $id]);
        } catch (\App\Shared\Exceptions\DomainException | CashAdvanceNotFoundException | \InvalidArgumentException $e) {
            $advance = $detailHandler(new GetCashAdvanceDetailQuery(cashAdvanceId: $id));

            if ($advance === null) {
                throw $this->createNotFoundException('Anticipo no encontrado.');
            }

            return $this->render('expense/cash_advance/edit.html.twig', [
                'advance' => $advance,
                'errors' => [$e->getMessage()],
            ], new Response(null, 422));
        }
    }

}
