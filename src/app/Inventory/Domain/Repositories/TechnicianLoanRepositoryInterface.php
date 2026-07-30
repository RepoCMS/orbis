<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;

interface TechnicianLoanRepositoryInterface
{
    public function findById(TechnicianLoanIdValueObject $id): ?TechnicianLoan;

    /** @return TechnicianLoan[] */
    public function findAll(?string $status = null): array;

    /** @return TechnicianLoan[] */
    public function findByFromTechnician(string $fromTechnicianId): array;

    /** @return TechnicianLoan[] */
    public function findByToTechnician(string $toTechnicianId): array;

    public function save(TechnicianLoan $technicianLoan): void;

    public function delete(TechnicianLoanIdValueObject $id): void;
}
