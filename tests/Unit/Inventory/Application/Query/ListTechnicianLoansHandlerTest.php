<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListTechnicianLoans\ListTechnicianLoansHandler;
use App\Inventory\Application\Query\ListTechnicianLoans\ListTechnicianLoansQuery;
use App\Inventory\Application\Query\Response\TechnicianLoanResponse;
use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Enums\TechnicianLoanStatus;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListTechnicianLoansHandlerTest extends TestCase
{
    private const LOAN_ID_1    = '01HZ0000000000000000000001';
    private const LOAN_ID_2    = '01HZ0000000000000000000002';
    private const SERIALIZED   = '01HZ0000000000000000000010';
    private const TECHNICIAN_A = '01HZ0000000000000000000020';
    private const TECHNICIAN_B = '01HZ0000000000000000000021';
    private const TECHNICIAN_C = '01HZ0000000000000000000022';

    private function makeLoan(
        string $id,
        TechnicianLoanStatus $status,
        string $from = self::TECHNICIAN_A,
        string $to = self::TECHNICIAN_B,
    ): TechnicianLoan {
        return TechnicianLoan::request(
            id:                new TechnicianLoanIdValueObject($id),
            serializedStockId: self::SERIALIZED,
            fromTechnicianId:  $from,
            toTechnicianId:    $to,
            ticketId:          null,
            notes:             null,
        );
    }

    public function testReturnsAllLoansWhenNoFilter(): void
    {
        $loan1 = $this->makeLoan(self::LOAN_ID_1, TechnicianLoanStatus::PENDING_LOAN);
        $loan2 = $this->makeLoan(self::LOAN_ID_2, TechnicianLoanStatus::APPROVED);

        $repo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->with(null)
            ->willReturn([$loan1, $loan2]);

        $handler = new ListTechnicianLoansHandler($repo);
        $result  = ($handler)(new ListTechnicianLoansQuery());

        self::assertCount(2, $result);
        self::assertInstanceOf(TechnicianLoanResponse::class, $result[0]);
        self::assertInstanceOf(TechnicianLoanResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoLoans(): void
    {
        $repo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListTechnicianLoansHandler($repo);
        $result  = ($handler)(new ListTechnicianLoansQuery());

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $loan = $this->makeLoan(self::LOAN_ID_1, TechnicianLoanStatus::PENDING_LOAN);

        $repo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$loan]);

        $handler = new ListTechnicianLoansHandler($repo);
        $result  = ($handler)(new ListTechnicianLoansQuery());

        self::assertCount(1, $result);
        self::assertSame(self::LOAN_ID_1, $result[0]->id);
        self::assertSame(self::SERIALIZED, $result[0]->serializedStockId);
        self::assertSame(self::TECHNICIAN_A, $result[0]->fromTechnicianId);
        self::assertSame(self::TECHNICIAN_B, $result[0]->toTechnicianId);
        self::assertNull($result[0]->ticketId);
        self::assertSame('pending_loan', $result[0]->status);
        self::assertSame('Pendiente de aprobaci' . "\xC3\xB3" . 'n', $result[0]->statusLabel);
        self::assertNull($result[0]->notes);
        self::assertNull($result[0]->approvedBy);
        self::assertNull($result[0]->approvedAt);
        self::assertNull($result[0]->rejectedBy);
        self::assertNull($result[0]->rejectedAt);
        self::assertNull($result[0]->rejectionReason);
        self::assertNull($result[0]->cancelledBy);
    }

    public function testFiltersByTechnicianId(): void
    {
        $loan1 = $this->makeLoan(self::LOAN_ID_1, TechnicianLoanStatus::PENDING_LOAN, self::TECHNICIAN_A, self::TECHNICIAN_B);
        $loan2 = $this->makeLoan(self::LOAN_ID_2, TechnicianLoanStatus::PENDING_LOAN, self::TECHNICIAN_C, self::TECHNICIAN_A);
        $loan3 = $this->makeLoan('01HZ0000000000000000000003', TechnicianLoanStatus::PENDING_LOAN, self::TECHNICIAN_C, self::TECHNICIAN_B);

        $repo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$loan1, $loan2, $loan3]);

        $handler = new ListTechnicianLoansHandler($repo);
        $result  = ($handler)(new ListTechnicianLoansQuery(technicianId: self::TECHNICIAN_A));

        self::assertCount(2, $result);
        self::assertSame(self::LOAN_ID_1, $result[0]->id);
        self::assertSame(self::LOAN_ID_2, $result[1]->id);
    }

    public function testFiltersByStatus(): void
    {
        $pending  = $this->makeLoan(self::LOAN_ID_1, TechnicianLoanStatus::PENDING_LOAN);
        $approved = $this->makeLoan(self::LOAN_ID_2, TechnicianLoanStatus::PENDING_LOAN);
        $approved->approve('01HZ0000000000000000000090');

        $repo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->with('approved')
            ->willReturn([$approved]);

        $handler = new ListTechnicianLoansHandler($repo);
        $result  = ($handler)(new ListTechnicianLoansQuery(status: 'approved'));

        self::assertCount(1, $result);
        self::assertSame('approved', $result[0]->status);
    }

    public function testTechnicianFilterReturnsEmptyWhenNoMatch(): void
    {
        $loan = $this->makeLoan(self::LOAN_ID_1, TechnicianLoanStatus::PENDING_LOAN, self::TECHNICIAN_A, self::TECHNICIAN_B);

        $repo = $this->createMock(TechnicianLoanRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$loan]);

        $handler = new ListTechnicianLoansHandler($repo);
        $result  = ($handler)(new ListTechnicianLoansQuery(technicianId: self::TECHNICIAN_C));

        self::assertCount(0, $result);
    }
}
