<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Location\Application\Query\ListCities\ListCitiesHandler;
use App\Location\Application\Query\ListCities\ListCitiesQuery;
use App\Organization\Application\Command\AddCityToZone\AddCityToZoneCommand;
use App\Organization\Application\Command\AddCityToZone\AddCityToZoneHandler;
use App\Organization\Application\Command\AssignTechnicianToZone\AssignTechnicianToZoneCommand;
use App\Organization\Application\Command\AssignTechnicianToZone\AssignTechnicianToZoneHandler;
use App\Organization\Application\Command\CreateServiceZone\CreateServiceZoneCommand;
use App\Organization\Application\Command\CreateServiceZone\CreateServiceZoneHandler;
use App\Organization\Application\Command\DeactivateServiceZone\DeactivateServiceZoneCommand;
use App\Organization\Application\Command\DeactivateServiceZone\DeactivateServiceZoneHandler;
use App\Organization\Application\Command\ReactivateServiceZone\ReactivateServiceZoneCommand;
use App\Organization\Application\Command\ReactivateServiceZone\ReactivateServiceZoneHandler;
use App\Organization\Application\Command\RemoveCityFromZone\RemoveCityFromZoneCommand;
use App\Organization\Application\Command\RemoveCityFromZone\RemoveCityFromZoneHandler;
use App\Organization\Application\Command\RemoveTechnicianFromZone\RemoveTechnicianFromZoneCommand;
use App\Organization\Application\Command\RemoveTechnicianFromZone\RemoveTechnicianFromZoneHandler;
use App\Organization\Application\Command\UpdateServiceZone\UpdateServiceZoneCommand;
use App\Organization\Application\Command\UpdateServiceZone\UpdateServiceZoneHandler;
use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Application\Query\FindServiceZoneById\FindServiceZoneByIdHandler;
use App\Organization\Application\Query\FindServiceZoneById\FindServiceZoneByIdQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesHandler;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/service-zones')]
final class ServiceZoneController extends AbstractController
{
    #[IsGranted('service_zone.view_all')]
    #[Route('', name: 'service_zone_list', methods: ['GET'])]
    public function list(ListServiceZonesHandler $listZones): Response
    {
        return $this->render('service_zone/list.html.twig', [
            'zones' => $listZones(new ListServiceZonesQuery()),
        ]);
    }

    #[IsGranted('service_zone.create')]
    #[Route('/new', name: 'service_zone_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('service_zone/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('service_zone.create')]
    #[Route('', name: 'service_zone_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateServiceZoneHandler $createZone,
    ): Response {
        $name   = trim((string) $request->request->get('name', ''));
        $errors = [];

        try {
            $id = ($createZone)(new CreateServiceZoneCommand(name: $name));
            $this->addFlash('success', sprintf('Zona de servicio "%s" creada exitosamente.', $name));

            return $this->redirectToRoute('service_zone_show', ['id' => $id]);
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        return $this->render('service_zone/form.html.twig', [
            'errors' => $errors,
            'input'  => compact('name'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('service_zone.view_all')]
    #[Route('/{id}', name: 'service_zone_show', methods: ['GET'])]
    public function show(
        string $id,
        FindServiceZoneByIdHandler $findZone,
        ListEmployeesHandler $listEmployees,
        ListCitiesHandler $listCities,
    ): Response {
        try {
            $zone = $findZone(new FindServiceZoneByIdQuery($id));
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        }

        $technicians = array_filter(
            $listEmployees(new ListEmployeesQuery())->items,
            static fn ($e) => $e->profile === 'technician',
        );

        $assignedTechnicians = array_filter(
            $technicians,
            static fn ($e) => $e->serviceZoneId === $id,
        );

        $availableTechnicians = array_filter(
            $technicians,
            static fn ($e) => $e->serviceZoneId === null,
        );

        $allCities = $listCities(new ListCitiesQuery());

        return $this->render('service_zone/show.html.twig', [
            'zone'                 => $zone,
            'assignedTechnicians'  => array_values($assignedTechnicians),
            'availableTechnicians' => array_values($availableTechnicians),
            'allCities'            => $allCities,
        ]);
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/edit', name: 'service_zone_edit', methods: ['POST'])]
    public function edit(
        string $id,
        Request $request,
        UpdateServiceZoneHandler $updateZone,
        FindServiceZoneByIdHandler $findZone,
    ): Response {
        $name   = trim((string) $request->request->get('name', ''));
        $errors = [];

        try {
            ($updateZone)(new UpdateServiceZoneCommand(serviceZoneId: $id, name: $name));
            $this->addFlash('success', 'Zona de servicio actualizada.');

            return $this->redirectToRoute('service_zone_show', ['id' => $id]);
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $zone = $findZone(new FindServiceZoneByIdQuery($id));
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        }

        return $this->render('service_zone/show.html.twig', [
            'zone'                 => $zone,
            'assignedTechnicians'  => [],
            'availableTechnicians' => [],
            'errors'               => $errors,
            'editMode'             => true,
            'editInput'            => compact('name'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/deactivate', name: 'service_zone_deactivate', methods: ['POST'])]
    public function deactivate(
        string $id,
        DeactivateServiceZoneHandler $deactivate,
    ): Response {
        try {
            ($deactivate)(new DeactivateServiceZoneCommand(serviceZoneId: $id));
            $this->addFlash('success', 'Zona de servicio desactivada.');
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_zone_show', ['id' => $id]);
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/reactivate', name: 'service_zone_reactivate', methods: ['POST'])]
    public function reactivate(
        string $id,
        ReactivateServiceZoneHandler $reactivate,
    ): Response {
        try {
            ($reactivate)(new ReactivateServiceZoneCommand(serviceZoneId: $id));
            $this->addFlash('success', 'Zona de servicio reactivada.');
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_zone_show', ['id' => $id]);
    }

    #[IsGranted('service_zone.assign_technician')]
    #[Route('/{id}/assign-technician', name: 'service_zone_assign_technician', methods: ['POST'])]
    public function assignTechnician(
        string $id,
        Request $request,
        AssignTechnicianToZoneHandler $assign,
    ): Response {
        $technicianId = trim((string) $request->request->get('technician_id', ''));

        try {
            ($assign)(new AssignTechnicianToZoneCommand(technicianId: $technicianId, serviceZoneId: $id));
            $this->addFlash('success', 'Técnico asignado a la zona.');
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_zone_show', ['id' => $id]);
    }

    #[IsGranted('service_zone.assign_technician')]
    #[Route('/{id}/remove-technician/{technicianId}', name: 'service_zone_remove_technician', methods: ['POST'])]
    public function removeTechnician(
        string $id,
        string $technicianId,
        RemoveTechnicianFromZoneHandler $remove,
    ): Response {
        try {
            ($remove)(new RemoveTechnicianFromZoneCommand(technicianId: $technicianId));
            $this->addFlash('success', 'Técnico removido de la zona.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_zone_show', ['id' => $id]);
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/add-city', name: 'service_zone_add_city', methods: ['POST'])]
    public function addCity(
        string $id,
        Request $request,
        AddCityToZoneHandler $addCity,
    ): Response {
        $cityId = trim((string) $request->request->get('city_id', ''));

        try {
            ($addCity)(new AddCityToZoneCommand(serviceZoneId: $id, cityId: $cityId));
            $this->addFlash('success', 'Ciudad asignada a la zona.');
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_zone_show', ['id' => $id]);
    }

    #[IsGranted('service_zone.update')]
    #[Route('/{id}/remove-city/{cityId}', name: 'service_zone_remove_city', methods: ['POST'])]
    public function removeCity(
        string $id,
        string $cityId,
        RemoveCityFromZoneHandler $removeCity,
    ): Response {
        try {
            ($removeCity)(new RemoveCityFromZoneCommand(serviceZoneId: $id, cityId: $cityId));
            $this->addFlash('success', 'Ciudad removida de la zona.');
        } catch (ServiceZoneNotFoundException) {
            throw $this->createNotFoundException('Zona de servicio no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_zone_show', ['id' => $id]);
    }
}
