<?php

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeVacationPeriodException;
use DateTimeImmutable;

readonly class EmployeeVacationPeriodValueObject
{
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;

    public function __construct(DateTimeImmutable $startDate, DateTimeImmutable $endDate)
    {
        if ($endDate < $startDate) {
            throw new EmployeeVacationPeriodException('La fecha de fin debe ser mayor o igual a la fecha de inicio.');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function days(): int
    {
        return $this->endDate->diff($this->startDate)->days + 1;
    }

    public function includeDate(DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }
}
