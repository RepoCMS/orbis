<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\CheckOverdueCashAdvances;

use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Events\CashAdvancePaymentPending;
use App\Expense\Domain\Events\CashAdvancePendingReview;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;

final readonly class CheckOverdueCashAdvancesHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(CheckOverdueCashAdvancesCommand $command): void
    {
        $now = new DateTimeImmutable();
        $advances = $this->cashAdvanceRepository->findByStatus(CashAdvanceStatus::OUTSTANDING);

        foreach ($advances as $advance) {
            $daysSinceGiven = (int) $now->diff($advance->givenAt())->days;

            if ($daysSinceGiven > 3) {
                $this->eventPublisher->publish(new CashAdvancePaymentPending(
                    cashAdvanceId: (string) $advance->id(),
                    employeeId: $advance->employeeId(),
                    amount: $advance->amount(),
                    daysSinceGiven: $daysSinceGiven,
                ));
            }
        }
    }
}
