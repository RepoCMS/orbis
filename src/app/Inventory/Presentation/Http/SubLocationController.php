<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\CreateSubLocation\CreateSubLocationCommand;
use App\Inventory\Application\Command\CreateSubLocation\CreateSubLocationHandler;
use App\Inventory\Application\Command\DeleteSubLocation\DeleteSubLocationCommand;
use App\Inventory\Application\Command\DeleteSubLocation\DeleteSubLocationHandler;
use App\Inventory\Application\Command\UpdateSubLocation\UpdateSubLocationCommand;
use App\Inventory\Application\Command\UpdateSubLocation\UpdateSubLocationHandler;
use App\Inventory\Application\Exception\SubLocationNotFoundException;
use App\Inventory\Application\Query\FindLocationById\FindLocationByIdHandler;
use App\Inventory\Application\Query\ListSubLocationsByLocation\ListSubLocationsByLocationHandler;
use App\Inventory\Application\Query\ListSubLocationsByLocation\ListSubLocationsByLocationQuery;
use App\Inventory\Domain\Enums\SubLocationType;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/locations/{locationId}/sub-locations')]
final class SubLocationController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_sublocation_list', methods: ['GET'])]
    public function list(
        string $locationId,
        ListSubLocationsByLocationHandler $handler,
        FindLocationByIdHandler $findLocation,
    ): Response {
        try {
            $location = $findLocation($locationId);
        } catch (\App\Inventory\Application\Exception\LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $tree = $handler(new ListSubLocationsByLocationQuery($locationId));

        return $this->render('inventory/sublocation/list.html.twig', [
            'location' => $location,
            'tree'     => $tree,
            'types'    => SubLocationType::cases(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_sublocation_new', methods: ['GET'])]
    public function newForm(
        string $locationId,
        FindLocationByIdHandler $findLocation,
        SubLocationRepositoryInterface $subLocationRepository,
    ): Response {
        try {
            $location = $findLocation($locationId);
        } catch (\App\Inventory\Application\Exception\LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/sublocation/form.html.twig', [
            'location'     => $location,
            'errors'       => [],
            'input'        => [],
            'types'        => SubLocationType::cases(),
            'parentOptions' => $subLocationRepository->findByLocationId($locationId),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_sublocation_create', methods: ['POST'])]
    public function create(
        string $locationId,
        Request $request,
        CreateSubLocationHandler $handler,
        FindLocationByIdHandler $findLocation,
        SubLocationRepositoryInterface $subLocationRepository,
    ): Response {
        $type     = trim((string) $request->request->get('type', ''));
        $name     = trim((string) $request->request->get('name', ''));
        $code     = trim((string) $request->request->get('code', ''));
        $parentId = trim((string) $request->request->get('parent_id', '')) ?: null;

        try {
            $location = $findLocation($locationId);
        } catch (\App\Inventory\Application\Exception\LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        try {
            ($handler)(new CreateSubLocationCommand(
                locationId: $locationId,
                type:       $type,
                name:       $name,
                code:       $code,
                parentId:   $parentId,
            ));
            $this->addFlash('success', sprintf('Sub-ubicación "%s" creada.', $name));

            return $this->redirectToRoute('inventory_sublocation_list', ['locationId' => $locationId]);
        } catch (DomainException $e) {
            return $this->render('inventory/sublocation/form.html.twig', [
                'location'      => $location,
                'errors'        => [$e->getMessage()],
                'input'         => compact('type', 'name', 'code', 'parentId'),
                'types'         => SubLocationType::cases(),
                'parentOptions' => $subLocationRepository->findByLocationId($locationId),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/edit', name: 'inventory_sublocation_edit', methods: ['GET'])]
    public function editForm(
        string $locationId,
        string $id,
        FindLocationByIdHandler $findLocation,
        SubLocationRepositoryInterface $subLocationRepository,
    ): Response {
        try {
            $location = $findLocation($locationId);
        } catch (\App\Inventory\Application\Exception\LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $subLocation = $subLocationRepository->findById(new SubLocationIdValueObject($id));

        if ($subLocation === null) {
            throw $this->createNotFoundException('Sub-ubicación no encontrada.');
        }

        return $this->render('inventory/sublocation/form.html.twig', [
            'location'      => $location,
            'subLocation'   => $subLocation,
            'errors'        => [],
            'input'         => [
                'name'      => $subLocation->name(),
                'code'      => $subLocation->code(),
                'type'      => $subLocation->type()->value,
                'parentId'  => $subLocation->parentId(),
            ],
            'types'         => SubLocationType::cases(),
            'parentOptions' => $subLocationRepository->findByLocationId($locationId),
        ]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}', name: 'inventory_sublocation_update', methods: ['POST', 'PUT'])]
    public function update(
        string $locationId,
        string $id,
        Request $request,
        UpdateSubLocationHandler $handler,
        FindLocationByIdHandler $findLocation,
        SubLocationRepositoryInterface $subLocationRepository,
    ): Response {
        $name = trim((string) $request->request->get('name', ''));
        $code = trim((string) $request->request->get('code', ''));

        try {
            $location = $findLocation($locationId);
        } catch (\App\Inventory\Application\Exception\LocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        try {
            ($handler)(new UpdateSubLocationCommand($id, $name, $code));
            $this->addFlash('success', sprintf('Sub-ubicación "%s" actualizada.', $name));

            return $this->redirectToRoute('inventory_sublocation_list', ['locationId' => $locationId]);
        } catch (SubLocationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (DomainException $e) {
            return $this->render('inventory/sublocation/form.html.twig', [
                'location'      => $location,
                'errors'        => [$e->getMessage()],
                'input'         => compact('name', 'code'),
                'types'         => SubLocationType::cases(),
                'parentOptions' => $subLocationRepository->findByLocationId($locationId),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/{id}/delete', name: 'inventory_sublocation_delete', methods: ['POST'])]
    public function delete(
        string $locationId,
        string $id,
        DeleteSubLocationHandler $handler,
    ): Response {
        try {
            ($handler)(new DeleteSubLocationCommand($id));
            $this->addFlash('success', 'Sub-ubicación eliminada.');
        } catch (SubLocationNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_sublocation_list', ['locationId' => $locationId]);
    }
}
