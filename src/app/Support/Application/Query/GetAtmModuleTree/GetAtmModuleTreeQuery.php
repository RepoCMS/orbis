<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetAtmModuleTree;

final readonly class GetAtmModuleTreeQuery
{
    public function __construct(
        public string $model,
    ) {}
}
