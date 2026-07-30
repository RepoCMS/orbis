<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\CreateModuleNode\CreateModuleNodeCommand;
use App\Support\Application\Command\CreateModuleNode\CreateModuleNodeHandler;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;
use PHPUnit\Framework\TestCase;

final class CreateModuleNodeHandlerTest extends TestCase
{
    private const NODE_ID   = '01HZ0000000000000000000001';
    private const PARENT_ID = '01HZ0000000000000000000002';

    public function testCreatesRootNode(): void
    {
        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('getNextPosition')->willReturn(0);

        $savedNode = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ModuleNode $node) use (&$savedNode): void {
                $savedNode = $node;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::NODE_ID);

        $handler = new CreateModuleNodeHandler($repo, $idGenerator);
        $result = ($handler)(new CreateModuleNodeCommand(
            model: 'mx8200st',
            code: 'BRM',
            label: 'Bill Recycler Module',
            components: ['Recycler', 'Cassette'],
        ));

        self::assertSame(self::NODE_ID, (string) $result->id());
        self::assertSame('BRM', $result->code());
        self::assertSame('Bill Recycler Module', $result->label());
        self::assertNull($result->parent());
    }

    public function testCreatesChildNode(): void
    {
        $parent = ModuleNode::create(
            id: ModuleNodeIdValueObject::fromString(self::PARENT_ID),
            model: \App\Support\Domain\AtmModel\AtmModel::MX8200ST,
            code: 'BRM',
            label: 'Bill Recycler Module',
        );

        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn($parent);
        $repo->method('getNextPosition')->willReturn(1);

        $savedNode = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ModuleNode $node) use (&$savedNode): void {
                $savedNode = $node;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::NODE_ID);

        $handler = new CreateModuleNodeHandler($repo, $idGenerator);
        $result = ($handler)(new CreateModuleNodeCommand(
            model: 'mx8200st',
            code: 'BC',
            label: 'Bill Cassette',
            parentId: self::PARENT_ID,
        ));

        self::assertSame('BC', $result->code());
        self::assertNotNull($result->parent());
        self::assertSame(self::PARENT_ID, (string) $result->parent()->id());
    }

    public function testThrowsWhenParentNotFound(): void
    {
        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new CreateModuleNodeHandler($repo, $idGenerator);

        $this->expectException(ModuleNodeNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Módulo del árbol con ID "%s" no encontrado.', self::PARENT_ID));

        ($handler)(new CreateModuleNodeCommand(
            model: 'mx8200st',
            code: 'BC',
            label: 'Bill Cassette',
            parentId: self::PARENT_ID,
        ));
    }
}
