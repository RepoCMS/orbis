<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Puerto para publicar Domain Events.
 *
 * Los Application Handlers inyectan esta interfaz y la llaman tras persistir
 * el agregado. La implementación concreta (Symfony EventDispatcher, Messenger,
 * etc.) vive en Infrastructure.
 */
interface DomainEventPublisherInterface
{
    public function publish(DomainEvent ...$events): void;
}
