<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

/**
 * Marker interface para entidades que tienen created_at y updated_at.
 * La infraestructura (TimestampsSubscriber) los gestiona automáticamente.
 * El dominio no los conoce ni expone getters para ellos.
 */
interface TimestampableInterface
{
}
