<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\ListVacationRequestsByEmployee\ListVacationRequestsByEmployeeHandler;
use App\Organization\Application\Query\ListVacationRequestsByEmployee\ListVacationRequestsByEmployeeQuery;
use App\Organization\Application\Query\Response\VacationRequestResponse;
use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListVacationRequestsByEmployeeHandlerTest extends TestCase
{
    private const EMPLOYEE_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';

    public function testReturnsRequests(): void
    {
        $request = VacationRequest::request(
            id: VacationRequestIdValueObject::fromString('01HZ0000000000000000000001'),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            startDate: new \DateTimeImmutable('+10 days'),
            endDate: new \DateTimeImmutable('+15 days'),
        );

        $repo = $this->createMock(VacationRequestRepositoryInterface::class);
        $repo->method('findAllByEmployee')->willReturn([$request]);

        $handler = new ListVacationRequestsByEmployeeHandler($repo);
        $result = ($handler)(new ListVacationRequestsByEmployeeQuery(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertCount(1, $result);
        self::assertInstanceOf(VacationRequestResponse::class, $result[0]);
    }

    public function testReturnsEmptyWhenNoRequests(): void
    {
        $repo = $this->createMock(VacationRequestRepositoryInterface::class);
        $repo->method('findAllByEmployee')->willReturn([]);

        $handler = new ListVacationRequestsByEmployeeHandler($repo);
        $result = ($handler)(new ListVacationRequestsByEmployeeQuery(
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertCount(0, $result);
    }
}
