<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRequestRepository implements RequestRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(RequestIdValueObject $id): ?Request
    {
        return $this->em->find(Request::class, $id);
    }

    public function findByReference(string $reference): ?Request
    {
        return $this->em->createQueryBuilder()
            ->select('r')->from(Request::class, 'r')
            ->where('r.reference = :ref')
            ->setParameter('ref', $reference)
            ->getQuery()->getOneOrNullResult();
    }

    public function findPending(): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')->from(Request::class, 'r')
            ->where('r.status = :status')
            ->setParameter('status', RequestStatus::PENDING->value)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')->from(Request::class, 'r')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findByIdWithItems(RequestIdValueObject $id): ?Request
    {
        return $this->em->createQueryBuilder()
            ->select('r', 'i')
            ->from(Request::class, 'r')
            ->leftJoin('r.items', 'i')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();
    }

    public function save(Request $request): void
    {
        $this->em->persist($request);
        $this->em->flush();
    }
}
