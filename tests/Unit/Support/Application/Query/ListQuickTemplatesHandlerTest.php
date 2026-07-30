<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListQuickTemplates\ListQuickTemplatesHandler;
use App\Support\Application\Query\ListQuickTemplates\ListQuickTemplatesQuery;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListQuickTemplatesHandlerTest extends TestCase
{
    private const CREATED_BY = '01HZ0000000000000000000001';

    public function testReturnsAllTemplates(): void
    {
        $template1 = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString('01HZ000000000000000000000A'),
            modelId: 'model-1',
            name: 'Plantilla A',
            createdBy: self::CREATED_BY,
        );
        $template2 = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString('01HZ000000000000000000000B'),
            modelId: 'model-2',
            name: 'Plantilla B',
            createdBy: self::CREATED_BY,
        );

        $repo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$template1, $template2]);

        $handler = new ListQuickTemplatesHandler($repo);
        $result = ($handler)(new ListQuickTemplatesQuery());

        self::assertCount(2, $result);
        self::assertSame('Plantilla A', $result[0]->name);
        self::assertSame('Plantilla B', $result[1]->name);
    }

    public function testReturnsEmptyArrayWhenNoTemplates(): void
    {
        $repo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListQuickTemplatesHandler($repo);
        $result = ($handler)(new ListQuickTemplatesQuery());

        self::assertCount(0, $result);
    }
}
