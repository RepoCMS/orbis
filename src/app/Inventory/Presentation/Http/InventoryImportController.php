<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Inventory\Application\Command\ImportNonserializedStock\ImportNonserializedStockCommand;
use App\Inventory\Application\Command\ImportNonserializedStock\ImportNonserializedStockHandler;
use App\Inventory\Application\Command\ImportProducts\ImportProductsCommand;
use App\Inventory\Application\Command\ImportProducts\ImportProductsHandler;
use App\Inventory\Application\Command\ImportPurchaseOrders\ImportPurchaseOrdersCommand;
use App\Inventory\Application\Command\ImportPurchaseOrders\ImportPurchaseOrdersHandler;
use App\Inventory\Application\Command\ImportSerializedStock\ImportSerializedStockCommand;
use App\Inventory\Application\Command\ImportSerializedStock\ImportSerializedStockHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/import')]
#[IsGranted('inventory.import')]
final class InventoryImportController extends AbstractController
{
    public function __construct(
        private readonly ImportProductsHandler $productsHandler,
        private readonly ImportSerializedStockHandler $serializedHandler,
        private readonly ImportNonserializedStockHandler $nonserializedHandler,
        private readonly ImportPurchaseOrdersHandler $purchaseOrdersHandler,
    ) {
    }

    #[Route('', name: 'inventory_import', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('inventory/import/index.html.twig');
    }

    #[Route('/products', name: 'inventory_import_products', methods: ['POST'])]
    public function importProducts(Request $request): Response
    {
        return $this->handleFileImport($request, $this->productsHandler, fn (string $path, bool $dry) => new ImportProductsCommand(filePath: $path, dryRun: $dry));
    }

    #[Route('/serialized-stock', name: 'inventory_import_serialized', methods: ['POST'])]
    public function importSerializedStock(Request $request): Response
    {
        return $this->handleFileImport($request, $this->serializedHandler, fn (string $path, bool $dry) => new ImportSerializedStockCommand(filePath: $path, dryRun: $dry));
    }

    #[Route('/nonserialized-stock', name: 'inventory_import_nonserialized', methods: ['POST'])]
    public function importNonserializedStock(Request $request): Response
    {
        return $this->handleFileImport($request, $this->nonserializedHandler, fn (string $path, bool $dry) => new ImportNonserializedStockCommand(filePath: $path, dryRun: $dry));
    }

    #[Route('/purchase-orders', name: 'inventory_import_po', methods: ['POST'])]
    public function importPurchaseOrders(Request $request): Response
    {
        $user = $this->getUser();
        $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : 'IMPORT';

        return $this->handleFileImport(
            $request,
            $this->purchaseOrdersHandler,
            fn (string $path, bool $dry) => new ImportPurchaseOrdersCommand(filePath: $path, createdBy: $userId, dryRun: $dry),
        );
    }

    #[Route('/template/{type}', name: 'inventory_import_template', methods: ['GET'])]
    public function downloadTemplate(string $type): Response
    {
        $templates = [
            'products' => [
                'headers' => ['code', 'name', 'description', 'tracking_type'],
                'example' => ['ATM-001', 'ATM Dispenser Module', 'Dispenser module for ATM Nautilus', 'serialized'],
                'filename' => 'productos_plantilla.csv',
            ],
            'serialized-stock' => [
                'headers' => ['product_code', 'serial_number', 'location_id'],
                'example' => ['ATM-001', 'SN-2024-00001', '01HXYZ...'],
                'filename' => 'stock_serializado_plantilla.csv',
            ],
            'nonserialized-stock' => [
                'headers' => ['product_code', 'location_id', 'quantity'],
                'example' => ['CABLE-USB', '01HXYZ...', '10'],
                'filename' => 'stock_no_serializado_plantilla.csv',
            ],
            'purchase-orders' => [
                'headers' => ['reference', 'notes', 'items'],
                'example' => ['PO-2024-001', 'Notas opcionales', 'ATM-001:5|CABLE-USB:20'],
                'filename' => 'ordenes_compra_plantilla.csv',
            ],
        ];

        if (!isset($templates[$type])) {
            throw $this->createNotFoundException('Tipo de plantilla no válido.');
        }

        $cfg = $templates[$type];
        $content = implode(',', $cfg['headers']) . "\n" . implode(',', $cfg['example']);

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $cfg['filename']),
            ],
        );
    }

    private function handleFileImport(
        Request $request,
        ImportProductsHandler|ImportSerializedStockHandler|ImportNonserializedStockHandler|ImportPurchaseOrdersHandler $handler,
        callable $commandFactory,
    ): Response {
        $file = $request->files->get('file');
        if ($file === null) {
            $this->addFlash('error', 'No se ha proporcionado ningún archivo.');

            return $this->redirectToRoute('inventory_import');
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            $this->addFlash('error', 'Formato no soportado. Use archivos CSV o XLSX.');

            return $this->redirectToRoute('inventory_import');
        }

        $dryRun = (bool) $request->request->get('dry_run', false);

        try {
            $result = $handler($commandFactory($file->getPathname(), $dryRun));

            if ($dryRun) {
                $this->addFlash('info', sprintf(
                    'Previsualización: %d importados, %d errores, %d omitidos.',
                    $result->imported,
                    $result->errors,
                    $result->skipped,
                ));
            } else {
                $this->addFlash('success', sprintf(
                    'Importación completada: %d registros importados, %d errores, %d omitidos.',
                    $result->imported,
                    $result->errors,
                    $result->skipped,
                ));
            }

            if (!empty($result->errorDetail)) {
                $this->addFlash('warning', 'Algunas filas presentaron errores. Revise el detalle.');
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Error al procesar la importación: ' . $e->getMessage());
        }

        return $this->redirectToRoute('inventory_import');
    }
}
