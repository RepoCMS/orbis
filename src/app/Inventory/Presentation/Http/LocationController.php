<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\CreateLocation\CreateLocationCommand;
use App\Inventory\Application\Command\CreateLocation\CreateLocationHandler;
use App\Inventory\Application\Command\DeleteLocation\DeleteLocationCommand;
use App\Inventory\Application\Command\DeleteLocation\DeleteLocationHandler;
use App\Inventory\Application\Command\UpdateLocation\UpdateLocationCommand;
use App\Inventory\Application\Command\UpdateLocation\UpdateLocationHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Query\FindLocationById\FindLocationByIdHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Domain\Enums\LocationType;
use App\Location\Application\Query\ListCities\ListCitiesHandler;
use App\Location\Application\Query\ListCities\ListCitiesQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/locations')]
final class LocationController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_location_list', methods: ['GET'])]
    public function list(ListLocationsHandler $handler): Response
    {
        return $this->render('inventory/location/list.html.twig', [
            'locations' => $handler(),
            'types'     => LocationType::cases(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_location_new', methods: ['GET'])]
    public function newForm(ListCitiesHandler $listCities): Response
    {
        return $this->render('inventory/location/form.html.twig', [
            'errors' => [],
            'input'  => [],
            'types'  => LocationType::cases(),
            'cities' => ($listCities)(new ListCitiesQuery()),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_location_create', methods: ['POST'])]
    public function create(Request $request, CreateLocationHandler $handler, ListCitiesHandler $listCities): Response
    {
        $name    = trim((string) $request->request->get('name', ''));
        $city    = trim((string) $request->request->get('city', ''));
        $address = trim((string) $request->request->get('address', ''));
        $type    = trim((string) $request->request->get('type', ''));

        try {
            ($handler)(new CreateLocationCommand($name, $city, $address, $type));
            $this->addFlash('success', sprintf('Ubicación "%s" creada.', $name));

            return $this->redirectToRoute('inventory_location_list');
        } catch (DomainException $e) {
            return $this->render('inventory/location/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('name', 'city', 'address', 'type'),
                'types'  => LocationType::cases(),
                'cities' => ($listCities)(new ListCitiesQuery()),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_location_show', methods: ['GET'])]
    public function show(string $id, FindLocationByIdHandler $findLocation): Response
    {
        try {
            $location = $findLocation($id);
        } catch (LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/location/show.html.twig', [
            'location' => $location,
        ]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/edit', name: 'inventory_location_edit', methods: ['GET'])]
    public function editForm(string $id, FindLocationByIdHandler $findLocation, ListCitiesHandler $listCities): Response
    {
        try {
            $location = $findLocation($id);
        } catch (LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/location/form.html.twig', [
            'location' => $location,
            'errors'   => [],
            'input'    => [
                'name'    => $location->name,
                'city'    => $location->city,
                'address' => $location->address,
                'type'    => $location->type,
            ],
            'types'  => LocationType::cases(),
            'cities' => ($listCities)(new ListCitiesQuery()),
        ]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}', name: 'inventory_location_update', methods: ['POST', 'PUT'])]
    public function update(string $id, Request $request, UpdateLocationHandler $handler, ListCitiesHandler $listCities): Response
    {
        $name    = trim((string) $request->request->get('name', ''));
        $city    = trim((string) $request->request->get('city', ''));
        $address = trim((string) $request->request->get('address', ''));

        try {
            ($handler)(new UpdateLocationCommand($id, $name, $city, $address));
            $this->addFlash('success', sprintf('Ubicación "%s" actualizada.', $name));

            return $this->redirectToRoute('inventory_location_show', ['id' => $id]);
        } catch (LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (DomainException $e) {
            return $this->render('inventory/location/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('name', 'city', 'address'),
                'types'  => LocationType::cases(),
                'cities' => ($listCities)(new ListCitiesQuery()),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/{id}/delete', name: 'inventory_location_delete', methods: ['POST'])]
    public function delete(string $id, DeleteLocationHandler $handler): Response
    {
        try {
            ($handler)(new DeleteLocationCommand($id));
            $this->addFlash('success', 'Ubicación eliminada.');
        } catch (LocationNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_location_list');
    }
}
