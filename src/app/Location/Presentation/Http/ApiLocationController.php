<?php

declare(strict_types=1);

namespace App\Location\Presentation\Http;

use App\Location\Application\Exception\InvalidLocationParameterException;
use App\Location\Application\Query\FindCityById\FindCityByIdHandler;
use App\Location\Application\Query\FindCityById\FindCityByIdQuery;
use App\Location\Application\Query\ListCitiesForProvince\ListCitiesForProvinceHandler;
use App\Location\Application\Query\ListCitiesForProvince\ListCitiesForProvinceQuery;
use App\Location\Application\Query\ListProvinces\ListProvincesHandler;
use App\Location\Application\Query\ListProvinces\ListProvincesQuery;
use App\Location\Application\Query\ListRegions\ListRegionsHandler;
use App\Location\Application\Query\ListRegions\ListRegionsQuery;
use App\Location\Application\Query\SearchCities\SearchCitiesHandler;
use App\Location\Application\Query\SearchCities\SearchCitiesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/location')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiLocationController extends AbstractController
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    #[Route('/regions', name: 'api_location_regions', methods: ['GET'])]
    public function regions(ListRegionsHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new ListRegionsQuery())]);
    }

    #[Route('/provinces', name: 'api_location_provinces', methods: ['GET'])]
    public function provinces(Request $request, ListProvincesHandler $handler): JsonResponse
    {
        try {
            $regionId = $request->query->get('regionId', '');

            if ('' === $regionId || !$this->isValidUlid($regionId)) {
                throw InvalidLocationParameterException::invalidUlid('regionId');
            }

            return $this->json(['data' => $handler(new ListProvincesQuery(
                regionId: $regionId,
            ))]);
        } catch (InvalidLocationParameterException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
    }

    #[Route('/cities', name: 'api_location_cities', methods: ['GET'])]
    public function cities(Request $request, ListCitiesForProvinceHandler $handler): JsonResponse
    {
        try {
            $provinceId = $request->query->get('provinceId', '');

            if ('' === $provinceId || !$this->isValidUlid($provinceId)) {
                throw InvalidLocationParameterException::invalidUlid('provinceId');
            }

            return $this->json(['data' => $handler(new ListCitiesForProvinceQuery(
                provinceId: $provinceId,
            ))]);
        } catch (InvalidLocationParameterException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
    }

    #[Route('/cities/search', name: 'api_location_cities_search', methods: ['GET'])]
    public function searchCities(Request $request, SearchCitiesHandler $handler): JsonResponse
    {
        try {
            $term = $request->query->get('q', '');
            $limit = (int) $request->query->get('limit', '20');

            if (strlen($term) < 2) {
                throw InvalidLocationParameterException::shortSearchTerm(2);
            }

            $limit = max(1, min($limit, 20));

            return $this->json(['data' => $handler(new SearchCitiesQuery(
                term: $term,
                limit: $limit,
            ))]);
        } catch (InvalidLocationParameterException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
    }

    #[Route('/cities/{id}', name: 'api_location_cities_find', methods: ['GET'])]
    public function findCityById(string $id, FindCityByIdHandler $handler): JsonResponse
    {
        try {
            if (!$this->isValidUlid($id)) {
                throw InvalidLocationParameterException::invalidUlid('id');
            }

            $result = $handler(new FindCityByIdQuery(cityId: $id));

            if ($result === null) {
                return $this->json(['error' => 'Ciudad no encontrada.'], 404);
            }

            return $this->json(['data' => $result]);
        } catch (InvalidLocationParameterException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
    }

    private function isValidUlid(string $value): bool
    {
        return 1 === preg_match(self::ULID_PATTERN, strtoupper(trim($value)));
    }
}
