<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\AtmModel\AtmModuleNode;

/** @see \App\Support\Domain\AtmModel\AtmModuleNode */
final readonly class ModuleTreeResponse
{
    /**
     * @param ModuleTreeResponse[] $children
     * @param string[]             $components
     */
    public function __construct(
        public string $code,
        public string $label,
        public array  $children = [],
        public array  $components = [],
    ) {
    }

    /**
     * @return ModuleTreeResponse[]
     */
    public static function fromNode(AtmModuleNode $node): self
    {
        $children = array_map(
            fn ($child) => self::fromNode($child),
            $node->children,
        );

        return new self(
            code: $node->code,
            label: $node->label,
            children: $children,
            components: $node->components,
        );
    }
}
