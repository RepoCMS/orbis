<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Presentation\Http;

use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemHandler;
use App\Expense\Application\Command\ApproveExpenseItem\ApproveExpenseItemHandler;
use App\Expense\Application\Command\ApproveOperatingExpense\ApproveOperatingExpenseHandler;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseHandler;
use App\Expense\Application\Command\RejectExpenseItem\RejectExpenseItemHandler;
use App\Expense\Application\Command\ResubmitExpense\ResubmitExpenseHandler;
use App\Expense\Application\Command\SubmitExpense\SubmitExpenseHandler;
use App\Expense\Application\Command\UploadEvidence\UploadEvidenceHandler;
use App\Expense\Application\Query\GetOperatingExpenseDetail\GetOperatingExpenseDetailHandler;
use App\Expense\Application\Query\ListOperatingExpenses\ListOperatingExpensesHandler;
use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\TicketResolverInterface;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Expense\Presentation\Http\ApiExpenseOperatingExpenseController;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiExpenseOperatingExpenseControllerTest extends TestCase
{
    private const EXPENSE_ID = '01HZ0000000000000000000001';
    private const ITEM_ID = '01HZ00000000000000000000A1';
    private const ULID = '01HZ000000000000000000000X';

    private function createController(): ApiExpenseOperatingExpenseController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn(string $id) => $id === 'parameter_bag');
        $container->method('getParameter')
            ->with('kernel.project_dir')
            ->willReturn(sys_get_temp_dir());

        $controller = new ApiExpenseOperatingExpenseController();
        $controller->setContainer($container);

        return $controller;
    }

    private function makeRequest(string $method, array $body = []): Request
    {
        return new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));
    }

    private function createCreateHandler(): CreateOperatingExpenseHandler
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expenseRepo->method('nextSequence')->willReturn(1);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::EXPENSE_ID);
        $employeeResolver = $this->createMock(EmployeeResolverInterface::class);
        $ticketResolver = $this->createMock(TicketResolverInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        return new CreateOperatingExpenseHandler(
            $expenseRepo, $idGenerator, $employeeResolver, $ticketResolver, $eventPublisher, $idempotency
        );
    }

    // ─── GET /api/v1/expense/operating-expenses ─────────────────────────────

    public function testListReturns200(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expenseRepo->method('findAll')->willReturn([]);
        $handler = new ListOperatingExpensesHandler($expenseRepo);

        $response = $this->createController()->list(new Request(), $handler);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertIsArray($data['data']);
    }

    // ─── POST /api/v1/expense/operating-expenses ────────────────────────────

    public function testCreateReturns201(): void
    {
        $handler = $this->createCreateHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => self::ULID,
            'reason' => 'Gastos de viaje',
            'startDate' => '2026-01-10',
            'endDate' => '2026-01-15',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::EXPENSE_ID, $data['data']['id']);
    }

    public function testCreateReturns422WhenEmployeeIdMissing(): void
    {
        $handler = $this->createCreateHandler();

        $request = $this->makeRequest('POST', [
            'reason' => 'Gastos de viaje',
            'startDate' => '2026-01-10',
            'endDate' => '2026-01-15',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateReturns422WhenReasonMissing(): void
    {
        $handler = $this->createCreateHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => self::ULID,
            'startDate' => '2026-01-10',
            'endDate' => '2026-01-15',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateReturns422WhenEmployeeIdNotUlid(): void
    {
        $handler = $this->createCreateHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => 'not-a-ulid',
            'reason' => 'Gastos de viaje',
            'startDate' => '2026-01-10',
            'endDate' => '2026-01-15',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateReturns400OnInvalidJson(): void
    {
        $handler = $this->createCreateHandler();

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not-json');

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ─── GET /api/v1/expense/operating-expenses/{id} ────────────────────────

    public function testShowReturns200WhenFound(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP-001',
            reason: 'Gastos de viaje',
            startDate: new DateTimeImmutable('2026-01-10'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );

        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expenseRepo->method('findById')->willReturn($expense);

        $handler = new GetOperatingExpenseDetailHandler($expenseRepo);
        $response = $this->createController()->show(self::EXPENSE_ID, $handler);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
    }

    public function testShowReturns404WhenNotFound(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expenseRepo->method('findById')->willReturn(null);

        $handler = new GetOperatingExpenseDetailHandler($expenseRepo);
        $response = $this->createController()->show(self::EXPENSE_ID, $handler);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    // ─── POST /api/v1/expense/operating-expenses/{id}/items ─────────────────

    public function testAddItemReturns422WhenTypeMissing(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $handler = new AddExpenseItemHandler($expenseRepo, $idGenerator, $eventPublisher);

        $request = $this->makeRequest('POST', [
            'amount' => 25.0,
        ]);

        $response = $this->createController()->addItem(self::EXPENSE_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testAddItemReturns422WhenAmountMissing(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $handler = new AddExpenseItemHandler($expenseRepo, $idGenerator, $eventPublisher);

        $request = $this->makeRequest('POST', [
            'type' => 'taxi',
        ]);

        $response = $this->createController()->addItem(self::EXPENSE_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    // ─── POST /api/v1/expense/operating-expenses/{id}/submit ────────────────

    public function testSubmitReturns200(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP-001',
            reason: 'Gastos de viaje',
            startDate: new DateTimeImmutable('2026-01-10'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::TAXI,
            amount: 25.0,
        );
        $expense->addItem($item);

        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expenseRepo->method('findById')->willReturn($expense);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitExpenseHandler($expenseRepo, $eventPublisher);

        $response = $this->createController()->submit(self::EXPENSE_ID, $handler);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame('submitted', $data['data']['status']);
    }

    // ─── POST /api/v1/expense/operating-expenses/{id}/resubmit ──────────────

    public function testResubmitReturns200(): void
    {
        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject(self::EXPENSE_ID),
            sequence: 1,
            employeeId: 'EMP-001',
            reason: 'Gastos de viaje',
            startDate: new DateTimeImmutable('2026-01-10'),
            endDate: new DateTimeImmutable('2026-01-15'),
        );
        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject(self::ITEM_ID),
            type: ExpenseItemType::TAXI,
            amount: 25.0,
        );
        $expense->addItem($item);
        $expense->submit();

        // Force status to REJECTED via reflection for resubmit test
        $ref = new \ReflectionClass($expense);
        $statusProp = $ref->getProperty('status');
        $statusProp->setValue($expense, \App\Expense\Domain\Enums\ExpenseStatus::REJECTED);

        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $expenseRepo->method('findById')->willReturn($expense);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ResubmitExpenseHandler($expenseRepo, $eventPublisher);

        $response = $this->createController()->resubmit(self::EXPENSE_ID, $handler);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame('submitted', $data['data']['status']);
    }

    // ─── POST /api/v1/expense/operating-expenses/{id}/approve ───────────────

    public function testApproveReturns422WhenApprovedByMissing(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $userResolver = $this->createMock(UserResolverInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $handler = new ApproveOperatingExpenseHandler($expenseRepo, $userResolver, $eventPublisher);

        $request = $this->makeRequest('POST', []);

        $response = $this->createController()->approve(self::EXPENSE_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    // ─── POST /api/v1/expense/operating-expenses/{id}/items/{itemId}/approve ─

    public function testApproveItemReturns422WhenReviewedByMissing(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $userResolver = $this->createMock(UserResolverInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $handler = new ApproveExpenseItemHandler($expenseRepo, $userResolver, $eventPublisher);

        $request = $this->makeRequest('POST', []);

        $response = $this->createController()->approveItem(self::EXPENSE_ID, self::ITEM_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    // ─── POST /api/v1/expense/operating-expenses/{id}/items/{itemId}/reject ─

    public function testRejectItemReturns422WhenReasonMissing(): void
    {
        $expenseRepo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $userResolver = $this->createMock(UserResolverInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $handler = new RejectExpenseItemHandler($expenseRepo, $userResolver, $eventPublisher);

        $request = $this->makeRequest('POST', [
            'reviewedBy' => self::ULID,
        ]);

        $response = $this->createController()->rejectItem(self::EXPENSE_ID, self::ITEM_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
}
