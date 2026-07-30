<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query\GetAtmModuleTree;

use App\Support\Application\Query\GetAtmModuleTree\GetAtmModuleTreeHandler;
use App\Support\Application\Query\GetAtmModuleTree\GetAtmModuleTreeQuery;
use App\Support\Application\Query\Response\ModuleTreeResponse;
use App\Support\Domain\AtmModel\AtmModuleTreeRegistry;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GetAtmModuleTreeHandlerTest extends TestCase
{
    private function createRegistry(): AtmModuleTreeRegistry
    {
        $repo = $this->createMock(ModuleNodeRepositoryInterface::class);
        $repo->method('findRootsByModel')->willReturn([]);

        return new AtmModuleTreeRegistry($repo);
    }

    public function testReturnsModuleTreeForRecyclerModel(): void
    {
        $handler = new GetAtmModuleTreeHandler($this->createRegistry());
        $result = ($handler)(new GetAtmModuleTreeQuery(model: 'mx8200st'));

        self::assertGreaterThan(2, count($result));
        self::assertInstanceOf(ModuleTreeResponse::class, $result[0]);
        self::assertSame('CE', $result[0]->code);
        self::assertSame('Control Electronics', $result[0]->label);

        $brm = $this->findNode($result, 'BRM');
        self::assertNotNull($brm);
        self::assertStringContainsString('Bill Recycler', $brm->label);

        $doc = $this->findNode($result, 'DOC_DEPOSIT');
        self::assertNotNull($doc);
    }

    public function testReturnsModuleTreeForDispenserModel(): void
    {
        $handler = new GetAtmModuleTreeHandler($this->createRegistry());
        $result = ($handler)(new GetAtmModuleTreeQuery(model: 'mx5800'));

        self::assertGreaterThan(2, count($result));
        self::assertInstanceOf(ModuleTreeResponse::class, $result[0]);

        $cdu = $this->findNode($result, 'CDU');
        self::assertNotNull($cdu);
        self::assertStringContainsString('Cash Dispenser', $cdu->label);
    }

    public function testReturnsCommonModulesForAllModels(): void
    {
        $handler = new GetAtmModuleTreeHandler($this->createRegistry());

        $recycler = ($handler)(new GetAtmModuleTreeQuery(model: 'mx8200qt'));
        $dispenser = ($handler)(new GetAtmModuleTreeQuery(model: 'mx5800t'));

        self::assertNotNull($this->findNode($recycler, 'CE'));
        self::assertNotNull($this->findNode($recycler, 'UI'));
        self::assertNotNull($this->findNode($recycler, 'PERIPH'));
        self::assertNotNull($this->findNode($recycler, 'SEC'));

        self::assertNotNull($this->findNode($dispenser, 'CE'));
        self::assertNotNull($this->findNode($dispenser, 'UI'));
        self::assertNotNull($this->findNode($dispenser, 'PERIPH'));
        self::assertNotNull($this->findNode($dispenser, 'SEC'));
    }

    public function testThrowsExceptionForInvalidModel(): void
    {
        $handler = new GetAtmModuleTreeHandler($this->createRegistry());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no es válido');

        ($handler)(new GetAtmModuleTreeQuery(model: 'invalid_model'));
    }

    /**
     * @param ModuleTreeResponse[] $nodes
     */
    private function findNode(array $nodes, string $code): ?ModuleTreeResponse
    {
        foreach ($nodes as $node) {
            if ($node->code === $code) {
                return $node;
            }
            $found = $this->findNode($node->children, $code);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
