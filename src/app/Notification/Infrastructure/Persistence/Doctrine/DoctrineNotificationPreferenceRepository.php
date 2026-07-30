<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine;

use App\Notification\Domain\Entities\NotificationPreference;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Repositories\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(NotificationPreferenceIdValueObject $id): ?NotificationPreference
    {
        return $this->em->find(NotificationPreference::class, (string) $id);
    }

    public function findByRecipient(RecipientType $recipientType, string $recipientId): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(NotificationPreference::class, 'p')
            ->where('p.recipientType = :recipientType')
            ->andWhere('p.recipientId = :recipientId')
            ->setParameter('recipientType', $recipientType)
            ->setParameter('recipientId', $recipientId)
            ->getQuery()
            ->getResult();
    }

    public function save(NotificationPreference $preference): void
    {
        $this->em->persist($preference);
        $this->em->flush();
    }
}
