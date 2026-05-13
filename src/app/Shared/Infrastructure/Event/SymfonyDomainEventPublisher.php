<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Adaptador que despacha Domain Events a través del EventDispatcher de Symfony.
 *
 * Symfony soporta despachar cualquier objeto (PSR-14), por lo que los
 * DomainEvents se despachan directamente sin necesidad de wrappers.
 *
 * Los listeners se registran usando Symfony's EventSubscriberInterface o
 * el atributo #[AsEventListener] apuntando a la clase del evento concreto.
 *
 * Ejemplo de listener:
 *   #[AsEventListener(event: UserRegistered::class)]
 *   public function onUserRegistered(UserRegistered $event): void { ... }
 */
final readonly class SymfonyDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
