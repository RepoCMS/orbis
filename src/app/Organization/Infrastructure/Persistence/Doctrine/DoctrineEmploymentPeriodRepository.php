<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Persistence\Doctrine;

use App\Organization\Domain\Entities\EmploymentPeriod;
use App\Organization\Domain\Enums\EmploymentPeriodStatus;
use App\Organization\Domain\Repositories\EmploymentPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmploymentPeriodIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEmploymentPeriodRepository implements EmploymentPeriodRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(EmploymentPeriod $period): void
    {
        $this->em->persist($period);
        $this->em->flush();
    }

    public function findById(EmploymentPeriodIdValueObject $id): ?EmploymentPeriod
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmploymentPeriod::class, 'p')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByEmployee(EmployeeIdValueObject $employeeId): ?EmploymentPeriod
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmploymentPeriod::class, 'p')
            ->where('p.employeeId = :employeeId')
            ->andWhere('p.status = :status')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('status', EmploymentPeriodStatus::ACTIVE->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return EmploymentPeriod[]
     */
    public function findAllByEmployee(EmployeeIdValueObject $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(EmploymentPeriod::class, 'p')
            ->where('p.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('p.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
