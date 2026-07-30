<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Presentation\Http;

use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryHandler;
use App\Expense\Application\Query\GetExpenseDashboardSummary\GetExpenseDashboardSummaryQuery;
use App\Expense\Application\Query\Response\DashboardSummaryResponse;
use App\Expense\Presentation\Http\ApiExpenseDashboardController;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiExpenseDashboardControllerTest extends TestCase
{
    private function createController(): ApiExpenseDashboardController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new ApiExpenseDashboardController();
        $controller->setContainer($container);

        return $controller;
    }

    public function testSummaryReturns200(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchOne')->willReturn('0');
        $dbal->method('fetchAllAssociative')->willReturn([]);

        $handler = new GetExpenseDashboardSummaryHandler($dbal);

        $response = $this->createController()->summary($handler);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('pendingApproval', $data['data']);
        self::assertArrayHasKey('totalOutstandingAdvances', $data['data']);
        self::assertArrayHasKey('totalExpenses', $data['data']);
        self::assertArrayHasKey('totalAdvances', $data['data']);
        self::assertArrayHasKey('expenseByType', $data['data']);
        self::assertArrayHasKey('advancesByTechnician', $data['data']);
        self::assertArrayHasKey('pendingSettlementAdvances', $data['data']);
    }

    public function testSummaryReturnsDataWhenExpensesAndAdvancesExist(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchOne')->willReturn('2');
        $dbal->method('fetchAllAssociative')->willReturn([]);

        $handler = new GetExpenseDashboardSummaryHandler($dbal);

        $response = $this->createController()->summary($handler);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data['data']);
    }
}
