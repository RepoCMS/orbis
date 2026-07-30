<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\DeleteModuleNode\DeleteModuleNodeCommand;
use App\Support\Application\Command\DeleteModuleNode\DeleteModuleNodeHandler;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteModuleNodeHandlerTest extends TestCase
{
    private const NODE_ID = '01HZ0000000000000000000001';

    public function testDeletesExistingNode(): void
    {
        $node = ModuleNode::create(
            id: ModuleNodeIdValueObject::fromString(self::NODE_ID),
            model: \App\Support\Domain\AtmModel\AtmModel::MX8200ST,
            code: 'BRM',
            label: 'Bill Recycler Module',
        );

        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn($node);
        $repo->expects($this->once())->method('delete')->with($node);

        $handler = new DeleteModuleNodeHandler($repo);
        ($handler)(new DeleteModuleNodeCommand(id: self::NODE_ID));
    }

    public function testThrowsWhenNodeNotFound(): void
    {
        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $repo->expects($this->never())->method('delete');

        $handler = new DeleteModuleNodeHandler($repo);

        $this->expectException(ModuleNodeNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Módulo del árbol con ID "%s" no encontrado.', self::NODE_ID));

        ($handler)(new DeleteModuleNodeCommand(id: self::NODE_ID));
    }
}
