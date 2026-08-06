<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListTechnicianLoans;

use App\Inventory\Application\Query\Response\TechnicianLoanResponse;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;

final readonly class ListTechnicianLoansHandler
{
    public function __construct(
        private TechnicianLoanRepositoryInterface $technicianLoanRepository,
    ) {
    }

    /** @return TechnicianLoanResponse[] */
    public function __invoke(ListTechnicianLoansQuery $query): array
    {
        $loans = $this->technicianLoanRepository->findAll($query->status);

        // Filter by technician if specified
        if ($query->technicianId !== null) {
            $loans = array_values(array_filter(
                $loans,
                static fn ($loan) =>
                    $loan->fromTechnicianId() === $query->technicianId
                    || $loan->toTechnicianId() === $query->technicianId
            ));
        }

        return array_map(
            static fn ($loan) => TechnicianLoanResponse::fromEntity($loan),
            $loans,
        );
    }
}
