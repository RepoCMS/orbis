<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Enums\OvertimeStatus;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOvertimeRecordRepository implements OvertimeRecordRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(OvertimeRecord $record): void
    {
        $this->em->persist($record);
        $this->em->flush();
    }

    public function findById(OvertimeRecordIdValueObject $id): ?OvertimeRecord
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(OvertimeRecord::class, 'r')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return OvertimeRecord[]
     */
    public function findByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(OvertimeRecord::class, 'r')
            ->where('r.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OvertimeRecord[]
     */
    public function findPendingByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(OvertimeRecord::class, 'r')
            ->where('r.employeeId = :employeeId')
            ->andWhere('r.status = :status')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('status', OvertimeStatus::PENDING->value)
            ->getQuery()
            ->getResult();
    }
}
