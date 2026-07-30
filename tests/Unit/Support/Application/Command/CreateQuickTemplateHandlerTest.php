<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Client\Domain\Entities\TerminalModel;
use App\Client\Domain\Repositories\TerminalModelRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Support\Application\Command\CreateQuickTemplate\CreateQuickTemplateCommand;
use App\Support\Application\Command\CreateQuickTemplate\CreateQuickTemplateHandler;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateQuickTemplateHandlerTest extends TestCase
{
    private const TEMPLATE_ID = '01HZ0000000000000000000001';
    private const MODEL_ID    = '01HZ0000000000000000000002';
    private const CREATED_BY  = '01HZ0000000000000000000003';

    private function makeTerminalModel(): TerminalModel
    {
        return TerminalModel::register(
            id: TerminalModelIdValueObject::fromString(self::MODEL_ID),
            name: 'NCR 1234',
            description: 'ATM NCR modelo 1234',
        );
    }

    public function testCreatesTemplateWithoutItems(): void
    {
        $terminalModel = $this->makeTerminalModel();

        $terminalModelRepo = $this->createMock(TerminalModelRepositoryInterface::class);
        $terminalModelRepo->method('findById')->willReturn($terminalModel);

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $savedTemplate = null;
        $templateRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (QuickTemplate $t) use (&$savedTemplate): void {
                $savedTemplate = $t;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TEMPLATE_ID);

        $handler = new CreateQuickTemplateHandler(
            templateRepo: $templateRepo,
            terminalModelRepo: $terminalModelRepo,
            idGenerator: $idGenerator,
        );

        $result = ($handler)(new CreateQuickTemplateCommand(
            modelId: self::MODEL_ID,
            name: 'Plantilla Preventivo NCR',
            createdBy: self::CREATED_BY,
            description: 'Mantenimiento preventivo estándar',
        ));

        self::assertSame(self::TEMPLATE_ID, $result);
        self::assertNotNull($savedTemplate);
        self::assertSame('Plantilla Preventivo NCR', $savedTemplate->name());
        self::assertSame('Mantenimiento preventivo estándar', $savedTemplate->description());
        self::assertCount(0, $savedTemplate->items());
    }

    public function testCreatesTemplateWithItems(): void
    {
        $terminalModel = $this->makeTerminalModel();

        $terminalModelRepo = $this->createMock(TerminalModelRepositoryInterface::class);
        $terminalModelRepo->method('findById')->willReturn($terminalModel);

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $savedTemplate = null;
        $templateRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (QuickTemplate $t) use (&$savedTemplate): void {
                $savedTemplate = $t;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TEMPLATE_ID);

        $handler = new CreateQuickTemplateHandler(
            templateRepo: $templateRepo,
            terminalModelRepo: $terminalModelRepo,
            idGenerator: $idGenerator,
        );

        $result = ($handler)(new CreateQuickTemplateCommand(
            modelId: self::MODEL_ID,
            name: 'Plantilla con items',
            createdBy: self::CREATED_BY,
            items: [
                [
                    'modelPartId' => 'PART-001',
                    'partNumber'  => 'PN-001',
                    'productCode' => 'PROD-001',
                    'description' => 'Revisar módulo de depósito',
                    'quantity'    => 1,
                    'sortOrder'   => 1,
                ],
                [
                    'description' => 'Limpiar lector de tarjetas',
                    'quantity'    => 1,
                    'sortOrder'   => 2,
                ],
            ],
        ));

        self::assertSame(self::TEMPLATE_ID, $result);
        self::assertNotNull($savedTemplate);
        self::assertCount(2, $savedTemplate->items());
        self::assertSame('Revisar módulo de depósito', $savedTemplate->items()[0]->description());
        self::assertSame('Limpiar lector de tarjetas', $savedTemplate->items()[1]->description());
    }

    public function testTerminalModelNotFoundThrows(): void
    {
        $terminalModelRepo = $this->createMock(TerminalModelRepositoryInterface::class);
        $terminalModelRepo->method('findById')->willReturn(null);

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new CreateQuickTemplateHandler(
            templateRepo: $templateRepo,
            terminalModelRepo: $terminalModelRepo,
            idGenerator: $idGenerator,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Modelo de terminal con ID "' . self::MODEL_ID . '" no encontrado.');

        ($handler)(new CreateQuickTemplateCommand(
            modelId: self::MODEL_ID,
            name: 'Plantilla',
            createdBy: self::CREATED_BY,
        ));
    }
}
