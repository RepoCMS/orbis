<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Support\Application\Command\UpdateModuleNode\UpdateModuleNodeCommand;
use App\Support\Application\Command\UpdateModuleNode\UpdateModuleNodeHandler;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateModuleNodeHandlerTest extends TestCase
{
    private const NODE_ID = '01HZ0000000000000000000001';

    public function testUpdatesExistingNode(): void
    {
        $node = ModuleNode::create(
            id: ModuleNodeIdValueObject::fromString(self::NODE_ID),
            model: \App\Support\Domain\AtmModel\AtmModel::MX8200ST,
            code: 'BRM',
            label: 'Bill Recycler Module',
        );

        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn($node);
        $repo->expects($this->once())
            ->method('save')
            ->with(self::callback(function (ModuleNode $n): bool {
                return $n->label() === 'Updated Label'
                    && $n->components() === ['PartA', 'PartB'];
            }));

        $handler = new UpdateModuleNodeHandler($repo);
        ($handler)(new UpdateModuleNodeCommand(
            id: self::NODE_ID,
            label: 'Updated Label',
            components: ['PartA', 'PartB'],
        ));

        self::assertSame('Updated Label', $node->label());
        self::assertSame(['PartA', 'PartB'], $node->components());
    }

    public function testUpdatesNodeWithEmptyComponents(): void
    {
        $node = ModuleNode::create(
            id: ModuleNodeIdValueObject::fromString(self::NODE_ID),
            model: \App\Support\Domain\AtmModel\AtmModel::MX8200ST,
            code: 'BRM',
            label: 'Bill Recycler Module',
            components: ['OldPart'],
        );

        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn($node);
        $repo->expects($this->once())
            ->method('save')
            ->with(self::callback(function (ModuleNode $n): bool {
                return $n->label() === 'Just Label' && $n->components() === [];
            }));

        $handler = new UpdateModuleNodeHandler($repo);
        ($handler)(new UpdateModuleNodeCommand(
            id: self::NODE_ID,
            label: 'Just Label',
        ));

        self::assertSame('Just Label', $node->label());
        self::assertSame([], $node->components());
    }

    public function testThrowsWhenNodeNotFound(): void
    {
        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $handler = new UpdateModuleNodeHandler($repo);

        $this->expectException(ModuleNodeNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Módulo del árbol con ID "%s" no encontrado.', self::NODE_ID));

        ($handler)(new UpdateModuleNodeCommand(id: self::NODE_ID, label: 'Anything'));
    }
}
