<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Message;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Wrapper message para transportar Domain Events a través de Messenger async.
 *
 * El bridge subscriber (DomainEventBridgeSubscriber) captura todos los DomainEvents
 * desde el EventDispatcher de Symfony y los envuelve en este mensaje para
 * ser procesados de forma asíncrona por los handlers de cada Bounded Context.
 */
final readonly class DomainEventMessage
{
    private function __construct(
        public string $eventClass,
        public string $serializedEvent,
        public string $occurredAt,
    ) {
    }

    public static function fromEvent(DomainEvent $event): self
    {
        return new self(
            eventClass: $event::class,
            serializedEvent: serialize($event),
            occurredAt: $event->occurredAt()->format('c'),
        );
    }

    public function restoreEvent(): DomainEvent
    {
        $event = unserialize($this->serializedEvent);

        if (!$event instanceof DomainEvent) {
            throw new \RuntimeException(sprintf(
                'Expected DomainEvent, got %s',
                $event::class,
            ));
        }

        return $event;
    }
}
