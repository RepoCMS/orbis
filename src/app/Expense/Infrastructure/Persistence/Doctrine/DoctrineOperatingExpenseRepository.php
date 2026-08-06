<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Persistence\Doctrine;

use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOperatingExpenseRepository implements OperatingExpenseRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(OperatingExpenseIdValueObject $id): ?OperatingExpense
    {
        $expense = $this->em->find(OperatingExpense::class, $id);
        if ($expense !== null && $expense->isDeleted()) {
            return null;
        }

        return $expense;
    }

    public function findByEmployeeId(string $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(OperatingExpense::class, 'e')
            ->where('e.employeeId = :employeeId')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(ExpenseStatus $status): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(OperatingExpense::class, 'e')
            ->where('e.status = :status')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('status', $status->value)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCashAdvanceId(string $cashAdvanceId): ?OperatingExpense
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(OperatingExpense::class, 'e')
            ->where('e.cashAdvanceId = :cashAdvanceId')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('cashAdvanceId', $cashAdvanceId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll(array $filters = []): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(OperatingExpense::class, 'e')
            ->where('e.deletedAt IS NULL')
            ->orderBy('e.createdAt', 'DESC');

        if (isset($filters['status']) && $filters['status'] instanceof ExpenseStatus) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']->value);
        }

        if (isset($filters['employeeId'])) {
            $qb->andWhere('e.employeeId = :employeeId')
                ->setParameter('employeeId', $filters['employeeId']);
        }

        return $qb->getQuery()->getResult();
    }

    public function nextSequence(string $employeeId): int
    {
        $year = (int) date('Y');
        $qb = $this->em->createQueryBuilder();
        $maxSequence = $qb
            ->select('MAX(e.sequence)')
            ->from(OperatingExpense::class, 'e')
            ->where('e.employeeId = :employeeId')
            ->andWhere('e.createdAt >= :start')
            ->andWhere('e.createdAt < :end')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('start', new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year)))
            ->setParameter('end', new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year + 1)))
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($maxSequence ?? 0) + 1;
    }

    public function save(OperatingExpense $expense): void
    {
        $this->em->persist($expense);
        $this->em->flush();
    }

    public function delete(OperatingExpenseIdValueObject $id): void
    {
        $expense = $this->em->find(OperatingExpense::class, $id);
        if ($expense !== null) {
            $expense->softDelete();
            $this->save($expense);
        }
    }
}
