<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Query;

use App\Organization\Application\Query\GetOrganizationDashboard\GetOrganizationDashboardHandler;
use App\Organization\Application\Query\GetOrganizationDashboard\GetOrganizationDashboardQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class GetOrganizationDashboardHandlerTest extends TestCase
{
    private function mockConnection(): Connection
    {
        return $this->createMock(Connection::class);
    }

    public function testReturnsSummaryWithCorrectCounts(): void
    {
        $connection = $this->mockConnection();

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [
                        ['status' => 'active', 'total' => 2],
                        ['status' => 'on_vacation', 'total' => 1],
                        ['status' => 'inactive', 'total' => 1],
                    ];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [
                        ['service_zone_id' => '01CXZ5NDEKTSV4RRFFQ69G5ABC', 'total' => 1],
                    ];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [
                        ['id' => '01CXZ5NDEKTSV4RRFFQ69G5ABC', 'name' => 'Zona Centro'],
                    ];
                }
                if (str_contains($sql, 'org_overtime_records')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY profile')) {
                    return [
                        ['profile' => 'technician', 'total' => 1, 'avgDays' => 365.0],
                        ['profile' => 'administrative', 'total' => 2, 'avgDays' => 730.0],
                    ];
                }
                if (str_contains($sql, 'org_vacation_requests')) {
                    return [];
                }

                return [];
            });

        $connection->method('fetchOne')
            ->willReturnCallback(function (string $sql): int {
                if (str_contains($sql, 'service_zones WHERE status')) {
                    return 1;
                }
                if (str_contains($sql, 'e.status')) {
                    return 1;
                }
                if (str_contains($sql, 'WHERE profile')) {
                    return 1;
                }

                return 0;
            });

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertSame(4, $result['totalEmployees']);
        self::assertSame(2, $result['active']);
        self::assertSame(1, $result['onVacation']);
        self::assertSame(2, $result['onDuty']);
        self::assertSame(1, $result['inactive']);
        self::assertSame(1, $result['totalZones']);
        self::assertCount(1, $result['employeesByZone']);
        self::assertSame(1, $result['availableTechnicians']);
        self::assertSame(1, $result['totalTechnicians']);
    }

    public function testReturnsZeroCountsWhenNoEmployees(): void
    {
        $connection = $this->mockConnection();

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [];
                }

                return [];
            });

        $connection->method('fetchOne')->willReturn(0);

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertSame(0, $result['totalEmployees']);
        self::assertSame(0, $result['active']);
        self::assertSame(0, $result['onVacation']);
        self::assertSame(0, $result['onDuty']);
        self::assertSame(0, $result['inactive']);
        self::assertSame(0, $result['totalZones']);
        self::assertEmpty($result['employeesByZone']);
        self::assertSame(0, $result['availableTechnicians']);
        self::assertSame(0, $result['totalTechnicians']);
    }

    public function testCountsEmployeesByZoneCorrectly(): void
    {
        $connection = $this->mockConnection();

        $zoneA = '01CXZ5NDEKTSV4RRFFQ69G5AAA';
        $zoneB = '01CYR6NDEKTSV4RRFFQ69G5BBB';

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) use ($zoneA, $zoneB): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [
                        ['status' => 'active', 'total' => 4],
                    ];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [
                        ['service_zone_id' => $zoneA, 'total' => 2],
                        ['service_zone_id' => $zoneB, 'total' => 1],
                    ];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [
                        ['id' => $zoneA, 'name' => 'Zona A'],
                        ['id' => $zoneB, 'name' => 'Zona B'],
                    ];
                }

                return [];
            });

        $connection->method('fetchOne')->willReturn(0);

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertSame(4, $result['totalEmployees']);
        self::assertCount(2, $result['employeesByZone']);
        self::assertSame(2, $result['employeesByZone'][$zoneA]);
        self::assertSame(1, $result['employeesByZone'][$zoneB]);
    }

    public function testOvertimeByEmployeeReturnsEmptyWhenNoRecords(): void
    {
        $connection = $this->mockConnection();

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [];
                }
                if (str_contains($sql, 'org_overtime_records')) {
                    return [];
                }

                return [];
            });

        $connection->method('fetchOne')->willReturn(0);

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertEmpty($result['overtimeByEmployee']);
    }

    public function testAvgTenureByProfileReturnsData(): void
    {
        $connection = $this->mockConnection();

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [];
                }
                if (str_contains($sql, 'org_overtime_records')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY profile')) {
                    return [
                        ['profile' => 'technician', 'total' => 5, 'avgDays' => 547.5],
                        ['profile' => 'coordinator', 'total' => 2, 'avgDays' => 1095.0],
                        ['profile' => 'administrative', 'total' => 3, 'avgDays' => 365.0],
                    ];
                }
                if (str_contains($sql, 'org_vacation_requests')) {
                    return [];
                }

                return [];
            });

        $connection->method('fetchOne')->willReturn(0);

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertCount(3, $result['avgTenureByProfile']);
        self::assertSame('technician', $result['avgTenureByProfile'][0]['profile']);
        self::assertSame('Técnico', $result['avgTenureByProfile'][0]['label']);
        self::assertSame(5, $result['avgTenureByProfile'][0]['total']);
        self::assertEquals(1.5, $result['avgTenureByProfile'][0]['avgYears']);
    }

    public function testUpcomingVacationsReturnsEmptyWhenNone(): void
    {
        $connection = $this->mockConnection();

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [];
                }
                if (str_contains($sql, 'org_overtime_records')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY profile')) {
                    return [];
                }
                if (str_contains($sql, 'org_vacation_requests')) {
                    return [];
                }

                return [];
            });

        $connection->method('fetchOne')->willReturn(0);

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertEmpty($result['upcomingVacations']);
    }

    public function testAvailableTechniciansCounts(): void
    {
        $connection = $this->mockConnection();

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql): array {
                if (str_contains($sql, 'GROUP BY status')) {
                    return [
                        ['status' => 'active', 'total' => 10],
                    ];
                }
                if (str_contains($sql, 'GROUP BY service_zone_id')) {
                    return [];
                }
                if (str_contains($sql, 'FROM service_zones')) {
                    return [];
                }
                if (str_contains($sql, 'org_overtime_records')) {
                    return [];
                }
                if (str_contains($sql, 'GROUP BY profile')) {
                    return [];
                }
                if (str_contains($sql, 'org_vacation_requests')) {
                    return [];
                }

                return [];
            });

        $connection->method('fetchOne')
            ->willReturnCallback(function (string $sql): int {
                if (str_contains($sql, 'e.status')) {
                    return 6;
                }
                if (str_contains($sql, 'WHERE profile')) {
                    return 8;
                }

                return 0;
            });

        $handler = new GetOrganizationDashboardHandler($connection);
        $result = ($handler)(new GetOrganizationDashboardQuery());

        self::assertSame(6, $result['availableTechnicians']);
        self::assertSame(8, $result['totalTechnicians']);
    }
}
