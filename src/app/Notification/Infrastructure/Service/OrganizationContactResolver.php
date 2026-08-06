<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Service;

use App\Notification\Domain\Services\ContactResolverInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class OrganizationContactResolver implements ContactResolverInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function resolve(string $recipientType, string $recipientId): array
    {
        if ($recipientType !== 'employee') {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        $employee = $this->employeeRepository->findById(
            EmployeeIdValueObject::fromString($recipientId),
        );

        if ($employee === null) {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        return [
            'email' => (string) $employee->email(),
            'phone' => $employee->phoneNumber(),
            'name' => $employee->fullName(),
        ];
    }
}
