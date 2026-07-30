<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\ScheduledMaintenance;
use App\Support\Domain\Repositories\ScheduledMaintenanceRepositoryInterface;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineScheduledMaintenanceRepository implements ScheduledMaintenanceRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(ScheduledMaintenanceIdValueObject $id): ?ScheduledMaintenance
    {
        return $this->em->find(ScheduledMaintenance::class, $id);
    }

    public function findByTerminalAssignmentId(string $terminalAssignmentId): array
    {
        return $this->em->createQueryBuilder()
            ->select('sm')
            ->from(ScheduledMaintenance::class, 'sm')
            ->where('sm.terminalAssignmentId = :terminalAssignmentId')
            ->andWhere('sm.deletedAt IS NULL')
            ->setParameter('terminalAssignmentId', $terminalAssignmentId)
            ->orderBy('sm.nextScheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActive(): array
    {
        return $this->em->createQueryBuilder()
            ->select('sm')
            ->from(ScheduledMaintenance::class, 'sm')
            ->where('sm.isActive = :active')
            ->andWhere('sm.deletedAt IS NULL')
            ->setParameter('active', true)
            ->orderBy('sm.nextScheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDueSoon(\DateTimeImmutable $referenceDate): array
    {
        $alertDate = $referenceDate->modify('+7 days');

        return $this->em->createQueryBuilder()
            ->select('sm')
            ->from(ScheduledMaintenance::class, 'sm')
            ->where('sm.isActive = :active')
            ->andWhere('sm.deletedAt IS NULL')
            ->andWhere('sm.nextScheduledAt <= :alertDate')
            ->setParameter('active', true)
            ->setParameter('alertDate', $alertDate)
            ->orderBy('sm.nextScheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdue(\DateTimeImmutable $referenceDate): array
    {
        return $this->em->createQueryBuilder()
            ->select('sm')
            ->from(ScheduledMaintenance::class, 'sm')
            ->where('sm.isActive = :active')
            ->andWhere('sm.deletedAt IS NULL')
            ->andWhere('sm.nextScheduledAt <= :now')
            ->setParameter('active', true)
            ->setParameter('now', $referenceDate)
            ->orderBy('sm.nextScheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('sm')
            ->from(ScheduledMaintenance::class, 'sm')
            ->where('sm.deletedAt IS NULL')
            ->orderBy('sm.nextScheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(ScheduledMaintenance $maintenance): void
    {
        $this->em->persist($maintenance);
        $this->em->flush();
    }

    public function delete(ScheduledMaintenanceIdValueObject $id): void
    {
        $maintenance = $this->findById($id);
        if ($maintenance === null) {
            return;
        }
        $maintenance->softDelete();
        $this->em->flush();
    }
}
