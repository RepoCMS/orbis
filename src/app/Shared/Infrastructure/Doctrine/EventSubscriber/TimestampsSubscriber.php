<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\EventSubscriber;

use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use ReflectionProperty;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class TimestampsSubscriber
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $now = new DateTimeImmutable();
        $this->set($entity, 'createdAt', $now);
        $this->set($entity, 'updatedAt', $now);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $this->set($entity, 'updatedAt', new DateTimeImmutable());

        $em       = $args->getObjectManager();
        $metadata = $em->getClassMetadata($entity::class);
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($metadata, $entity);
    }

    private function set(object $entity, string $property, DateTimeImmutable $value): void
    {
        $ref = new ReflectionProperty($entity, $property);
        $ref->setValue($entity, $value);
    }
}
