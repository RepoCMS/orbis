<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Persistence\Doctrine;

use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCashAdvanceRepository implements CashAdvanceRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(CashAdvanceIdValueObject $id): ?CashAdvance
    {
        $cashAdvance = $this->em->find(CashAdvance::class, $id);
        if ($cashAdvance !== null && $cashAdvance->isDeleted()) {
            return null;
        }
        return $cashAdvance;
    }

    public function findByEmployeeId(string $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(CashAdvance::class, 'c')
            ->where('c.employeeId = :employeeId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('c.givenAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(CashAdvanceStatus $status): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(CashAdvance::class, 'c')
            ->where('c.status = :status')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('status', $status->value)
            ->orderBy('c.givenAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdue(int $days): array
    {
        $cutoff = new \DateTimeImmutable(sprintf('-%d days', $days));

        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(CashAdvance::class, 'c')
            ->where('c.status = :status')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.givenAt <= :cutoff')
            ->setParameter('status', CashAdvanceStatus::OUTSTANDING->value)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('c.givenAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(CashAdvance::class, 'c')
            ->where('c.deletedAt IS NULL')
            ->orderBy('c.givenAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function nextAdvanceNumber(int $year): string
    {
        $qb = $this->em->createQueryBuilder();
        $count = $qb
            ->select('COUNT(c.id)')
            ->from(CashAdvance::class, 'c')
            ->where('c.givenAt >= :start')
            ->andWhere('c.givenAt < :end')
            ->setParameter('start', new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year)))
            ->setParameter('end', new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year + 1)))
            ->getQuery()
            ->getSingleScalarResult();

        return sprintf('ADV-%04d-%03d', $year, (int) ($count ?? 0) + 1);
    }

    public function save(CashAdvance $cashAdvance): void
    {
        $this->em->persist($cashAdvance);
        $this->em->flush();
    }

    public function delete(CashAdvanceIdValueObject $id): void
    {
        $cashAdvance = $this->em->find(CashAdvance::class, $id);
        if ($cashAdvance !== null) {
            $cashAdvance->softDelete();
            $this->save($cashAdvance);
        }
    }
}
