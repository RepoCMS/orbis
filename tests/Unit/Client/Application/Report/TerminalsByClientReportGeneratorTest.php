<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Report;

use App\Client\Application\Report\TerminalsByClient\TerminalsByClientReportGenerator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class TerminalsByClientReportGeneratorTest extends TestCase
{
    private function mockConnection(array $rows): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn($rows);

        return $connection;
    }

    public function testGetTitleAndHeaders(): void
    {
        $generator = new TerminalsByClientReportGenerator($this->mockConnection([]));

        self::assertSame('Terminales por Cliente', $generator->getTitle());
        self::assertSame([
            'Cliente',
            'RUC',
            'Serial',
            'Tipo',
            'Modelo',
            'Estado',
            'Asignación',
            'ID en Cliente',
            'Sitio',
            'Estado Asignación',
        ], $generator->getHeaders());
    }

    public function testReturnsEmptyArrayWhenNoData(): void
    {
        $generator = new TerminalsByClientReportGenerator($this->mockConnection([]));

        self::assertSame([], $generator->generate());
    }

    public function testMapsTerminalRowWithAssignment(): void
    {
        $rows = [[
            'id'                   => '01HX0000000000000000000001',
            'serial'               => 'SN-1001',
            'type'                 => 'atm',
            'atm_model_code'       => 'NDC-100',
            'status'               => 'active',
            'client_name'          => 'Banco Test',
            'ruc'                  => '1799999999001',
            'assignment_name'      => 'Cajero Principal',
            'id_assigned_by_client' => 'ATM-001',
            'site_name'            => 'Matriz Quito',
            'assignment_status'    => 'operative',
        ]];

        $generator = new TerminalsByClientReportGenerator($this->mockConnection($rows));
        $result    = $generator->generate();

        self::assertCount(1, $result);
        self::assertSame([
            'Cliente'           => 'Banco Test',
            'RUC'               => '1799999999001',
            'Serial'            => 'SN-1001',
            'Tipo'              => 'atm',
            'Modelo'            => 'NDC-100',
            'Estado'            => 'Activo',
            'Asignación'        => 'Cajero Principal',
            'ID en Cliente'     => 'ATM-001',
            'Sitio'             => 'Matriz Quito',
            'Estado Asignación' => 'Operativo',
        ], $result[0]);
    }

    public function testMapsTerminalWithoutAssignment(): void
    {
        $rows = [[
            'id'                   => '01HX0000000000000000000002',
            'serial'               => 'SN-1002',
            'type'                 => null,
            'atm_model_code'       => null,
            'status'               => 'inactive',
            'client_name'          => 'Banco Test',
            'ruc'                  => '1799999999001',
            'assignment_name'      => null,
            'id_assigned_by_client' => null,
            'site_name'            => null,
            'assignment_status'    => null,
        ]];

        $generator = new TerminalsByClientReportGenerator($this->mockConnection($rows));
        $result    = $generator->generate();

        self::assertSame('SN-1002', $result[0]['Serial']);
        self::assertSame('Inactivo', $result[0]['Estado']);
        self::assertSame('Sin asignación', $result[0]['Asignación']);
        self::assertSame('—', $result[0]['Sitio']);
        self::assertSame('—', $result[0]['Estado Asignación']);
    }
}
