<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

class ApplicationException extends RuntimeException
{
    protected array $context = [];

    /**
     * Agrega contexto adicional a la excepción.
     *
     * @param array $context Datos estructurados adicionales
     * @return $this Para usar fluent interface
     */
    public function withContext(array $context): self
    {
        $this->context = $context;
        
        return $this;
    }

    /**
     * Obtiene el contexto de la excepción.
     *
     * @return array Datos de contexto estructurados
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
