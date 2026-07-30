<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Organization\Application\Command\CreateServiceZone\CreateServiceZoneCommand;
use App\Organization\Application\Command\CreateServiceZone\CreateServiceZoneHandler;
use App\Organization\Application\Command\DeactivateServiceZone\DeactivateServiceZoneCommand;
use App\Organization\Application\Command\DeactivateServiceZone\DeactivateServiceZoneHandler;
use App\Organization\Application\Command\DeleteServiceZone\DeleteServiceZoneCommand;
use App\Organization\Application\Command\DeleteServiceZone\DeleteServiceZoneHandler;
use App\Organization\Application\Command\ReactivateServiceZone\ReactivateServiceZoneCommand;
use App\Organization\Application\Command\ReactivateServiceZone\ReactivateServiceZoneHandler;
use App\Organization\Application\Command\TransferCity\TransferCityCommand;
use App\Organization\Application\Command\TransferCity\TransferCityHandler;
use App\Organization\Application\Command\UpdateServiceZone\UpdateServiceZoneCommand;
use App\Organization\Application\Command\UpdateServiceZone\UpdateServiceZoneHandler;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Application\Query\FindServiceZoneById\FindServiceZoneByIdHandler;
use App\Organization\Application\Query\FindServiceZoneById\FindServiceZoneByIdQuery;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesHandler;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/organization/service-zones')]
final class ApiServiceZoneController extends AbstractController
{
    #[IsGranted('service_zone.view_all')]
    #[Route('', name: 'api_organization_service_zones_list', methods: ['GET'])]
    public function list(ListServiceZonesHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new ListServiceZonesQuery())]);
    }

    #[IsGranted('service_zone.create')]
    #[Route('', name: 'api_organization_service_zones_create', methods: ['POST'])]
    public function create(Request $request, CreateServiceZoneHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $name = trim((string) ($payload['name'] ?? ''));

        try {
            $id = ($handler)(new CreateServiceZoneCommand(name: $name));

            return $this->json(['data' => ['id' => $id]], Response::HTTP_CREATED);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('service_zone.view_all')]
    #[Route('/{id}', name: 'api_organization_service_zones_show', methods: ['GET'])]
    public function show(string $id, FindServiceZoneByIdHandler $handler): JsonResponse
    {
        try {
            return $this->json(['data' => $handler(new FindServiceZoneByIdQuery($id))]);
        } catch (ServiceZoneNotFoundException) {
            return $this->json(['error' => 'Zona de servicio no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}', name: 'api_organization_service_zones_update', methods: ['PATCH'])]
    public function update(string $id, Request $request, UpdateServiceZoneHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $name = trim((string) ($payload['name'] ?? ''));

        try {
            ($handler)(new UpdateServiceZoneCommand(serviceZoneId: $id, name: $name));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ServiceZoneNotFoundException) {
            return $this->json(['error' => 'Zona de servicio no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}', name: 'api_organization_service_zones_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteServiceZoneHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeleteServiceZoneCommand(serviceZoneId: $id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (ServiceZoneNotFoundException) {
            return $this->json(['error' => 'Zona de servicio no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/deactivate', name: 'api_organization_service_zones_deactivate', methods: ['POST'])]
    public function deactivate(string $id, DeactivateServiceZoneHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeactivateServiceZoneCommand(serviceZoneId: $id));

            return $this->json(['data' => ['id' => $id, 'status' => 'inactive']]);
        } catch (ServiceZoneNotFoundException) {
            return $this->json(['error' => 'Zona de servicio no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/reactivate', name: 'api_organization_service_zones_reactivate', methods: ['POST'])]
    public function reactivate(string $id, ReactivateServiceZoneHandler $handler): JsonResponse
    {
        try {
            ($handler)(new ReactivateServiceZoneCommand(serviceZoneId: $id));

            return $this->json(['data' => ['id' => $id, 'status' => 'active']]);
        } catch (ServiceZoneNotFoundException) {
            return $this->json(['error' => 'Zona de servicio no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/transfer-city', name: 'api_organization_service_zones_transfer_city', methods: ['POST'])]
    public function transferCity(string $id, Request $request, TransferCityHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new TransferCityCommand(
                serviceZoneId: $id,
                cityId:        trim((string) ($payload['cityId'] ?? '')),
                targetZoneId:  trim((string) ($payload['targetZoneId'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ServiceZoneNotFoundException) {
            return $this->json(['error' => 'Zona de servicio no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
