<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AddPurchaseOrderItem\AddPurchaseOrderItemCommand;
use App\Inventory\Application\Command\AddPurchaseOrderItem\AddPurchaseOrderItemHandler;
use App\Inventory\Application\Command\CancelPurchaseOrder\CancelPurchaseOrderCommand;
use App\Inventory\Application\Command\CancelPurchaseOrder\CancelPurchaseOrderHandler;
use App\Inventory\Application\Command\CreatePurchaseOrder\CreatePurchaseOrderCommand;
use App\Inventory\Application\Command\CreatePurchaseOrder\CreatePurchaseOrderHandler;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Application\Query\FindPurchaseOrderById\FindPurchaseOrderByIdHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\ListPurchaseOrders\ListPurchaseOrdersHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/purchase-orders')]
final class PurchaseOrderController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_po_list', methods: ['GET'])]
    public function list(ListPurchaseOrdersHandler $handler): Response
    {
        return $this->render('inventory/purchase_order/list.html.twig', [
            'purchaseOrders' => $handler(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_po_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('inventory/purchase_order/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_po_create', methods: ['POST'])]
    public function create(Request $request, CreatePurchaseOrderHandler $handler): Response
    {
        $reference = trim((string) $request->request->get('reference', ''));
        $notes     = trim((string) $request->request->get('notes', '')) ?: null;
        $userId    = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new CreatePurchaseOrderCommand($reference, $notes, $userId));
            $this->addFlash('success', sprintf('Orden de compra "%s" creada.', $reference));

            return $this->redirectToRoute('inventory_po_list');
        } catch (PurchaseOrderReferenceAlreadyExistsException | DomainException $e) {
            return $this->render('inventory/purchase_order/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('reference', 'notes'),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_po_show', methods: ['GET'])]
    public function show(
        string $id,
        FindPurchaseOrderByIdHandler $findPo,
        ListProductsHandler $listProducts,
    ): Response {
        try {
            $data = $findPo($id);
        } catch (PurchaseOrderNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/purchase_order/show.html.twig', [
            'po'       => $data['po'],
            'items'    => $data['items'],
            'products' => $listProducts(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/{id}/items', name: 'inventory_po_add_item', methods: ['POST'])]
    public function addItem(string $id, Request $request, AddPurchaseOrderItemHandler $handler): Response
    {
        $productId       = trim((string) $request->request->get('product_id', ''));
        $quantityOrdered = (int) $request->request->get('quantity_ordered', 1);

        try {
            ($handler)(new AddPurchaseOrderItemCommand($id, $productId, $quantityOrdered));
            $this->addFlash('success', 'Ítem agregado a la orden.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_po_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/cancel', name: 'inventory_po_cancel', methods: ['POST'])]
    public function cancel(string $id, CancelPurchaseOrderHandler $handler): Response
    {
        try {
            ($handler)(new CancelPurchaseOrderCommand($id));
            $this->addFlash('success', 'Orden de compra cancelada.');
        } catch (PurchaseOrderNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_po_list');
    }
}
