<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineContactRepository implements ContactRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(ContactIdValueObject $id): ?Contact
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Contact::class, 'c')
            ->where('c.id = :id')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserId(UserIdValueObject $userId): ?Contact
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Contact::class, 'c')
            ->where('c.userId = :userId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByClientId(ClientIdValueObject $clientId): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Contact::class, 'c')
            ->where('c.clientId = :clientId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Contact::class, 'c')
            ->where('c.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function save(Contact $contact): void
    {
        $this->em->persist($contact);
        $this->em->flush();
    }

    public function delete(ContactIdValueObject $id): void
    {
        $this->em->createQueryBuilder()
            ->update(Contact::class, 'c')
            ->set('c.deletedAt', ':now')
            ->where('c.id = :id')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
