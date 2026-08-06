<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\CreateCashAdvance;

use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Application\IdempotentHandlerTrait;
use App\Shared\Application\Service\IdempotencyService;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class CreateCashAdvanceHandler
{
    use IdempotentHandlerTrait;

    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepository,
        private IdGeneratorInterface $idGenerator,
        private EmployeeResolverInterface $employeeResolver,
        private UserResolverInterface $userResolver,
        private DomainEventPublisherInterface $eventPublisher,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    public function __invoke(CreateCashAdvanceCommand $command): string
    {
        $this->ensureUnique(
            IdempotencyService::buildKey(CreateCashAdvanceCommand::class, serialize($command)),
            CreateCashAdvanceCommand::class,
            $command->givenBy,
        );
        $this->employeeResolver->findActive($command->employeeId);
        $this->userResolver->findActive($command->givenBy);

        $now = new DateTimeImmutable();
        $year = (int) $now->format('Y');
        $advanceNumber = $this->cashAdvanceRepository->nextAdvanceNumber($year);

        $cashAdvance = CashAdvance::create(
            id: new CashAdvanceIdValueObject($this->idGenerator->generate()),
            advanceNumber: $advanceNumber,
            employeeId: $command->employeeId,
            givenBy: $command->givenBy,
            amount: $command->amount,
            givenAt: new DateTimeImmutable($command->givenAt),
        );

        $this->cashAdvanceRepository->save($cashAdvance);

        foreach ($cashAdvance->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return (string) $cashAdvance->id();
    }
}
