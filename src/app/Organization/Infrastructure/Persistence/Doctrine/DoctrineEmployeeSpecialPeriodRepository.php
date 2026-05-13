<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEmployeeSpecialPeriodRepository implements EmployeeSpecialPeriodRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(EmployeeSpecialPeriod $period): void
    {
        $this->em->persist($period);
        $this->em->flush();
    }

    public function findById(EmployeeSpecialPeriodIdValueObject $id): ?EmployeeSpecialPeriod
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmployeeSpecialPeriod::class, 'p')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByEmployeeAndType(
        EmployeeIdValueObject $employeeId,
        EmployeeSpecialPeriodType $type,
    ): ?EmployeeSpecialPeriod {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmployeeSpecialPeriod::class, 'p')
            ->where('p.employeeId = :employeeId')
            ->andWhere('p.type = :type')
            ->andWhere('p.status IN (:activeStatuses)')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('type', $type->value)
            ->setParameter('activeStatuses', [
                EmployeeSpecialPeriodStatus::SCHEDULED->value,
                EmployeeSpecialPeriodStatus::IN_PROGRESS->value,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDueToStart(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmployeeSpecialPeriod::class, 'p')
            ->where('p.status = :status')
            ->andWhere('p.startedAt <= :now')
            ->setParameter('status', EmployeeSpecialPeriodStatus::SCHEDULED->value)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findDueToEnd(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmployeeSpecialPeriod::class, 'p')
            ->where('p.status = :status')
            ->andWhere('p.endedAt <= :now')
            ->setParameter('status', EmployeeSpecialPeriodStatus::IN_PROGRESS->value)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findOverlappingByEmployee(
        EmployeeIdValueObject $employeeId,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endedAt,
    ): array {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmployeeSpecialPeriod::class, 'p')
            ->where('p.employeeId = :employeeId')
            ->andWhere('p.status IN (:activeStatuses)')
            ->andWhere('p.startedAt < :endedAt')
            ->andWhere('p.endedAt > :startedAt')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('activeStatuses', [
                EmployeeSpecialPeriodStatus::SCHEDULED->value,
                EmployeeSpecialPeriodStatus::IN_PROGRESS->value,
            ])
            ->setParameter('startedAt', $startedAt)
            ->setParameter('endedAt', $endedAt)
            ->getQuery()
            ->getResult();
    }

    public function findAllByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmployeeSpecialPeriod::class, 'p')
            ->where('p.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('p.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
