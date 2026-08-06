<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Presentation\Http;

use App\Expense\Application\Command\CreateCashAdvance\CreateCashAdvanceHandler;
use App\Expense\Application\Command\SettleCashAdvance\SettleCashAdvanceHandler;
use App\Expense\Application\Query\ListCashAdvances\ListCashAdvancesHandler;
use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Presentation\Http\ApiExpenseCashAdvanceController;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiExpenseCashAdvanceControllerTest extends TestCase
{
    private const CASH_ADVANCE_ID = '01HZ0000000000000000000001';
    private const ULID = '01HZ000000000000000000000X';

    private function createController(): ApiExpenseCashAdvanceController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new ApiExpenseCashAdvanceController();
        $controller->setContainer($container);

        return $controller;
    }

    private function makeRequest(string $method, array $body = []): Request
    {
        return new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));
    }

    private function createHandler(): CreateCashAdvanceHandler
    {
        $cashAdvanceRepo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $cashAdvanceRepo->method('nextAdvanceNumber')->willReturn('ADV-2026-0001');
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::CASH_ADVANCE_ID);
        $employeeResolver = $this->createMock(EmployeeResolverInterface::class);
        $userResolver = $this->createMock(UserResolverInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);

        return new CreateCashAdvanceHandler(
            $cashAdvanceRepo,
            $idGenerator,
            $employeeResolver,
            $userResolver,
            $eventPublisher,
            $idempotency
        );
    }

    // ─── GET /api/v1/expense/cash-advances ─────────────────────────────────

    public function testListReturns200(): void
    {
        $cashAdvanceRepo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $cashAdvanceRepo->method('findAll')->willReturn([]);
        $handler = new ListCashAdvancesHandler($cashAdvanceRepo);

        $response = $this->createController()->list(new Request(), $handler);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertIsArray($data['data']);
    }

    // ─── POST /api/v1/expense/cash-advances ────────────────────────────────

    public function testCreateReturns201(): void
    {
        $handler = $this->createHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => self::ULID,
            'givenBy' => self::ULID,
            'amount' => 500.0,
            'givenAt' => '2026-01-10',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertSame(self::CASH_ADVANCE_ID, $data['data']['id']);
        self::assertSame('outstanding', $data['data']['status']);
    }

    public function testCreateReturns422WhenEmployeeIdMissing(): void
    {
        $handler = $this->createHandler();

        $request = $this->makeRequest('POST', [
            'givenBy' => self::ULID,
            'amount' => 500.0,
            'givenAt' => '2026-01-10',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testCreateReturns422WhenGivenByMissing(): void
    {
        $handler = $this->createHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => self::ULID,
            'amount' => 500.0,
            'givenAt' => '2026-01-10',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateReturns422WhenAmountInvalid(): void
    {
        $handler = $this->createHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => self::ULID,
            'givenBy' => self::ULID,
            'amount' => -10,
            'givenAt' => '2026-01-10',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateReturns422WhenEmployeeIdNotUlid(): void
    {
        $handler = $this->createHandler();

        $request = $this->makeRequest('POST', [
            'employeeId' => 'not-a-ulid',
            'givenBy' => self::ULID,
            'amount' => 500.0,
            'givenAt' => '2026-01-10',
        ]);

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateReturns422OnInvalidJson(): void
    {
        $handler = $this->createHandler();

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not-json');

        $response = $this->createController()->create($request, $handler);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ─── POST /api/v1/expense/cash-advances/{id}/settle ─────────────────────

    private function createSettleHandler(): SettleCashAdvanceHandler
    {
        $cashAdvanceRepo = $this->createMock(CashAdvanceRepositoryInterface::class);
        $cashAdvanceRepo->method('findById')->willReturn(null);
        $userResolver = $this->createMock(UserResolverInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        return new SettleCashAdvanceHandler($cashAdvanceRepo, $userResolver, $eventPublisher);
    }

    public function testSettleValidatesInputBeforeHandlerCall(): void
    {
        $handler = $this->createSettleHandler();

        // Missing settlementConfirmedBy
        $request = $this->makeRequest('POST', [
            'settlementDirection' => 'tech_to_company',
        ]);

        $response = $this->createController()->settle(self::CASH_ADVANCE_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testSettleReturns422WhenDirectionMissing(): void
    {
        $handler = $this->createSettleHandler();

        $request = $this->makeRequest('POST', [
            'settlementConfirmedBy' => self::ULID,
        ]);

        $response = $this->createController()->settle(self::CASH_ADVANCE_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testSettleReturns422WhenConfirmedByNotUlid(): void
    {
        $handler = $this->createSettleHandler();

        $request = $this->makeRequest('POST', [
            'settlementConfirmedBy' => 'not-a-ulid',
            'settlementDirection' => 'tech_to_company',
        ]);

        $response = $this->createController()->settle(self::CASH_ADVANCE_ID, $request, $handler);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testSettleReturns422OnInvalidJson(): void
    {
        $handler = $this->createSettleHandler();

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not-json');

        $response = $this->createController()->settle(self::CASH_ADVANCE_ID, $request, $handler);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
