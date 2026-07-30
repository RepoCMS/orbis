<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineContractRepository implements ContractRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(ContractIdValueObject $id): ?Contract
    {
        return $this->em->find(Contract::class, $id);
    }

    public function findByClientId(ClientIdValueObject $clientId): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Contract::class, 'c')
            ->where('c.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Contract::class, 'c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Contract $contract): void
    {
        $this->em->persist($contract);
        $this->em->flush();
    }
}
