<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\RejectVacationRequest\RejectVacationRequestCommand;
use App\Organization\Application\Command\RejectVacationRequest\RejectVacationRequestHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\VacationRequestStatus;
use App\Organization\Domain\Events\VacationRejected;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class RejectVacationRequestHandlerTest extends TestCase
{
    private const REQUEST_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';
    private const REVIEWER_ID = '01HZ0000000000000000000002';

    public function testRejectsVacationRequest(): void
    {
        $request = VacationRequest::request(
            id: VacationRequestIdValueObject::fromString(self::REQUEST_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            startDate: new \DateTimeImmutable('+10 days'),
            endDate: new \DateTimeImmutable('+15 days'),
        );

        $requestRepo = $this->createMock(VacationRequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->once())->method('save');

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(
            Employee::hire(
                id: EmployeeIdValueObject::fromString(self::REVIEWER_ID),
                identificationNumber: new EmployeeIdentificationNumberValueObject('1710034065'),
                firstName: 'Revisor Test',
                lastName: 'Apellido Test',
                email: new EmployeeEmailValueObject('revisor@example.com'),
                phoneNumber: '0998765432',
                profile: EmployeeProfile::COORDINATOR,
                hiredAt: new \DateTimeImmutable('2023-01-01'),
            ),
        );

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(VacationRejected::class));

        $handler = new RejectVacationRequestHandler($requestRepo, $employeeRepo, $publisher);
        ($handler)(new RejectVacationRequestCommand(
            requestId: self::REQUEST_ID,
            reviewedBy: self::REVIEWER_ID,
            reason: 'No cubre fechas requeridas',
        ));

        self::assertSame(VacationRequestStatus::REJECTED, $request->status());
        self::assertSame('No cubre fechas requeridas', $request->rejectionReason());
    }

    public function testThrowsWhenReviewerNotFound(): void
    {
        $request = VacationRequest::request(
            id: VacationRequestIdValueObject::fromString(self::REQUEST_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            startDate: new \DateTimeImmutable('+10 days'),
            endDate: new \DateTimeImmutable('+15 days'),
        );

        $requestRepo = $this->createMock(VacationRequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);

        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $employeeRepo->method('findById')->willReturn(null);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RejectVacationRequestHandler($requestRepo, $employeeRepo, $publisher);

        $this->expectException(EmployeeNotFoundException::class);

        ($handler)(new RejectVacationRequestCommand(
            requestId: self::REQUEST_ID,
            reviewedBy: '01HZ0000000000000000000099',
            reason: 'No aplica',
        ));
    }
}
