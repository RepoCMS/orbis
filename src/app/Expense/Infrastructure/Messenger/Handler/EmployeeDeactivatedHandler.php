<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Messenger\Handler;

use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asíncrono para el evento EmployeeDeactivated.
 *
 * Antes: Expense\Infrastructure\EventListener\EmployeeDeactivatedSubscriber
 * (sincrónico vía EventDispatcher).
 * Ahora: recibe DomainEventMessage a través de Messenger async.
 *
 * Marca como PENDING_REVIEW los CashAdvance OUTSTANDING del empleado.
 */
#[AsMessageHandler]
final readonly class EmployeeDeactivatedHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepository,
    ) {}

    public function __invoke(DomainEventMessage $message): void
    {
        if ($message->eventClass !== EmployeeDeactivated::class) {
            return;
        }

        /** @var EmployeeDeactivated $event */
        $event = $message->restoreEvent();

        $advances = $this->cashAdvanceRepository->findByEmployeeId($event->employeeId);

        foreach ($advances as $advance) {
            if ($advance->status() !== CashAdvanceStatus::OUTSTANDING) {
                continue;
            }

            $advance->markPendingReview();
            $this->cashAdvanceRepository->save($advance);
        }
    }
}
