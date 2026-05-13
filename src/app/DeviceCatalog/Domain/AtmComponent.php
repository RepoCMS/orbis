<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Domain;

final class AtmComponent
{
    private AtmComponentId  $id;
    private ?AtmComponentId $parentId;
    private string          $name;
    private ?string         $aliases;
    private AtmComponentLevel $level;
    private int             $sortOrder;
    private bool            $active;

    private function __construct() {}

    public static function reconstitute(
        string  $id,
        ?string $parentId,
        string  $name,
        ?string $aliases,
        string  $level,
        int     $sortOrder,
        bool    $active,
    ): self {
        $obj = new self();
        $obj->id         = AtmComponentId::fromString($id);
        $obj->parentId   = $parentId ? AtmComponentId::fromString($parentId) : null;
        $obj->name       = $name;
        $obj->aliases    = $aliases;
        $obj->level      = AtmComponentLevel::from($level);
        $obj->sortOrder  = $sortOrder;
        $obj->active     = $active;
        return $obj;
    }

    public function id(): AtmComponentId    { return $this->id; }
    public function parentId(): ?AtmComponentId { return $this->parentId; }
    public function name(): string          { return $this->name; }
    public function aliases(): ?string      { return $this->aliases; }
    public function level(): AtmComponentLevel { return $this->level; }
    public function sortOrder(): int        { return $this->sortOrder; }
    public function isActive(): bool        { return $this->active; }
}
