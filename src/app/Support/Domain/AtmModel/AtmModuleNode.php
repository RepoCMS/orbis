<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

/**
 * Nodo del árbol de módulos de ATM.
 *
 * Representa un módulo, submódulo o componente dentro de la jerarquía
 * de partes de un cajero automático Hyosung MX Series.
 *
 * Este es un Value Object inmutable que forma parte del AtmModuleTree.
 */
final readonly class AtmModuleNode
{
    /**
     * @param AtmModuleNode[] $children   Sub-módulos jerárquicos
     * @param string[]        $components Nombres de componentes finales seleccionables
     */
    public function __construct(
        public string $code,
        public string $label,
        public array $children = [],
        public array $components = [],
    ) {}
}
