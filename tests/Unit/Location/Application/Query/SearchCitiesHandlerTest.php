<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Application\Query;

use App\Location\Application\Exception\InvalidLocationParameterException;
use App\Location\Application\Query\Response\CitySearchResponse;
use App\Location\Application\Query\SearchCities\SearchCitiesHandler;
use App\Location\Application\Query\SearchCities\SearchCitiesQuery;
use App\Location\Domain\Entities\City;
use App\Location\Domain\Entities\Province;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Location\Domain\Repositories\ProvinceRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SearchCitiesHandlerTest extends TestCase
{
    private const CITY_ID     = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const PROVINCE_ID = '01HZ0000000000000000000001';
    private const REGION_ID   = '01HZ0000000000000000000002';

    public function testSearchesCitiesSuccessfully(): void
    {
        $city = City::reconstitute(self::CITY_ID, self::PROVINCE_ID, 'Guayaquil');
        $province = Province::reconstitute(self::PROVINCE_ID, self::REGION_ID, 'Guayas');

        $cityRepo = $this->createMock(CityRepositoryInterface::class);
        $cityRepo->method('searchByName')->willReturn([$city]);

        $provinceRepo = $this->createMock(ProvinceRepositoryInterface::class);
        $provinceRepo->method('findById')->willReturn($province);

        $handler = new SearchCitiesHandler($cityRepo, $provinceRepo);
        $result = ($handler)(new SearchCitiesQuery(term: 'Guay', limit: 10));

        self::assertCount(1, $result);
        self::assertInstanceOf(CitySearchResponse::class, $result[0]);
        self::assertSame(self::CITY_ID, $result[0]->id);
        self::assertSame('Guayaquil', $result[0]->name);
        self::assertSame('Guayas', $result[0]->provinceName);
    }

    public function testThrowsWhenTermTooShort(): void
    {
        $cityRepo = $this->createMock(CityRepositoryInterface::class);
        $provinceRepo = $this->createMock(ProvinceRepositoryInterface::class);

        $handler = new SearchCitiesHandler($cityRepo, $provinceRepo);

        $this->expectException(InvalidLocationParameterException::class);

        ($handler)(new SearchCitiesQuery(term: 'G', limit: 10));
    }

    public function testReturnsEmptyWhenNoMatches(): void
    {
        $cityRepo = $this->createMock(CityRepositoryInterface::class);
        $cityRepo->method('searchByName')->willReturn([]);

        $provinceRepo = $this->createMock(ProvinceRepositoryInterface::class);

        $handler = new SearchCitiesHandler($cityRepo, $provinceRepo);
        $result = ($handler)(new SearchCitiesQuery(term: 'XYZ', limit: 10));

        self::assertSame([], $result);
    }
}
