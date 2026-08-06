<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine;

use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineNotificationTemplateRepository implements NotificationTemplateRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(NotificationTemplateIdValueObject $id): ?NotificationTemplate
    {
        return $this->em->find(NotificationTemplate::class, (string) $id);
    }

    public function findByEventTypeAndChannel(EventType $eventType, NotificationChannel $channel): ?NotificationTemplate
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(NotificationTemplate::class, 't')
            ->where('t.eventType = :eventType')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.isActive = :isActive')
            ->setParameter('eventType', $eventType)
            ->setParameter('channel', $channel)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll(?EventType $eventType = null, ?NotificationChannel $channel = null, ?bool $isActive = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(NotificationTemplate::class, 't');

        if ($eventType !== null) {
            $qb->andWhere('t.eventType = :eventType')
                ->setParameter('eventType', $eventType);
        }

        if ($channel !== null) {
            $qb->andWhere('t.channel = :channel')
                ->setParameter('channel', $channel);
        }

        if ($isActive !== null) {
            $qb->andWhere('t.isActive = :isActive')
                ->setParameter('isActive', $isActive);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(NotificationTemplate $template): void
    {
        $this->em->persist($template);
        $this->em->flush();
    }
}
