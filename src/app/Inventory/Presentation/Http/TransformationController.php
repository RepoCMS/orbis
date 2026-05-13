<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\RecordTransformation\RecordTransformationCommand;
use App\Inventory\Application\Command\RecordTransformation\RecordTransformationHandler;
use App\Inventory\Application\Query\FindTransformationById\FindTransformationByIdHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListTransformations\ListTransformationsHandler;
use App\Inventory\Application\Query\Response\SerializedStockResponse;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/transformations')]
final class TransformationController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_transformation_list', methods: ['GET'])]
    public function list(ListTransformationsHandler $handler): Response
    {
        return $this->render('inventory/transformation/list.html.twig', [
            'transformations' => $handler(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_transformation_new', methods: ['GET'])]
    public function newForm(
        ListLocationsHandler $listLocations,
        SerializedStockRepositoryInterface $serializedStockRepository,
    ): Response {
        $availableStock = array_map(
            static fn ($s) => SerializedStockResponse::fromEntity($s),
            $serializedStockRepository->findAvailable(),
        );

        return $this->render('inventory/transformation/form.html.twig', [
            'errors'    => [],
            'input'     => [],
            'locations' => $listLocations(),
            'stock'     => $availableStock,
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_transformation_create', methods: ['POST'])]
    public function create(
        Request $request,
        RecordTransformationHandler $handler,
        ListLocationsHandler $listLocations,
        SerializedStockRepositoryInterface $serializedStockRepository,
    ): Response {
        $serializedStockId = trim((string) $request->request->get('serialized_stock_id', ''));
        $laboratoryId      = trim((string) $request->request->get('laboratory_id', ''));
        $newProductId      = trim((string) $request->request->get('new_product_id', '')) ?: null;
        $newSerialNumber   = trim((string) $request->request->get('new_serial_number', '')) ?: null;
        $notes             = trim((string) $request->request->get('notes', '')) ?: null;
        $userId            = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new RecordTransformationCommand(
                serializedStockId: $serializedStockId,
                laboratoryId:      $laboratoryId,
                performedById:     $userId,
                newProductId:      $newProductId,
                newSerialNumber:   $newSerialNumber,
                notes:             $notes,
            ));

            $this->addFlash('success', 'Transformación registrada.');

            return $this->redirectToRoute('inventory_transformation_list');
        } catch (\Exception $e) {
            $availableStock = array_map(
                static fn ($s) => SerializedStockResponse::fromEntity($s),
                $serializedStockRepository->findAvailable(),
            );

            return $this->render('inventory/transformation/form.html.twig', [
                'errors'    => [$e->getMessage()],
                'input'     => compact('serializedStockId', 'laboratoryId', 'newProductId', 'newSerialNumber', 'notes'),
                'locations' => $listLocations(),
                'stock'     => $availableStock,
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_transformation_show', methods: ['GET'])]
    public function show(string $id, FindTransformationByIdHandler $handler): Response
    {
        try {
            $transformation = $handler($id);
        } catch (\RuntimeException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/transformation/show.html.twig', [
            'transformation' => $transformation,
        ]);
    }
}
