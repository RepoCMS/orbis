<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CancelVacationRequest\CancelVacationRequestCommand;
use App\Organization\Application\Command\CancelVacationRequest\CancelVacationRequestHandler;
use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Enums\VacationRequestStatus;
use App\Organization\Domain\Events\VacationCancelled;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class CancelVacationRequestHandlerTest extends TestCase
{
    private const REQUEST_ID = '01JARZ3NDEKTSV4RRFFQ69G5FV';
    private const EMPLOYEE_ID = '01HZ0000000000000000000001';

    public function testCancelsPendingRequest(): void
    {
        $request = VacationRequest::request(
            id: VacationRequestIdValueObject::fromString(self::REQUEST_ID),
            employeeId: EmployeeIdValueObject::fromString(self::EMPLOYEE_ID),
            startDate: new \DateTimeImmutable('+10 days'),
            endDate: new \DateTimeImmutable('+15 days'),
        );

        $repo = $this->createMock(VacationRequestRepositoryInterface::class);
        $repo->method('findById')->willReturn($request);
        $repo->expects($this->once())->method('save');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(VacationCancelled::class));

        $handler = new CancelVacationRequestHandler($repo, $publisher);
        ($handler)(new CancelVacationRequestCommand(
            requestId: self::REQUEST_ID,
            employeeId: self::EMPLOYEE_ID,
        ));

        self::assertSame(VacationRequestStatus::CANCELLED, $request->status());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $repo = $this->createMock(VacationRequestRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new CancelVacationRequestHandler($repo, $publisher);

        $this->expectException(VacationRequestException::class);

        ($handler)(new CancelVacationRequestCommand(
            requestId: '01HZ0000000000000000000099',
            employeeId: self::EMPLOYEE_ID,
        ));
    }
}
