<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Report\Movement\MovementReportGenerator;
use App\Inventory\Application\Report\PhysicalCount\PhysicalCountReportGenerator;
use App\Inventory\Application\Report\ResignationReturn\ResignationReturnReportGenerator;
use App\Inventory\Application\Report\ValuedInventory\ValuedInventoryReportGenerator;
use App\Shared\Infrastructure\Export\ExcelExportService;
use App\Shared\Infrastructure\Export\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/report')]
#[IsGranted('inventory.report')]
final class InventoryReportController extends AbstractController
{
    public function __construct(
        private readonly ValuedInventoryReportGenerator $valuedReportGenerator,
        private readonly MovementReportGenerator $movementReportGenerator,
        private readonly ResignationReturnReportGenerator $resignationReturnReportGenerator,
        private readonly PhysicalCountReportGenerator $physicalCountReportGenerator,
        private readonly ExcelExportService $excelExport,
        private readonly PdfExportService $pdfExport,
    ) {
    }

    #[Route('/valued.{_format}', name: 'inventory_report_valued', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function valued(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->valuedReportGenerator->generate($request->query->all());
        $title = $this->valuedReportGenerator->getTitle();
        $headers = $this->valuedReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/movements.{_format}', name: 'inventory_report_movements', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function movements(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->movementReportGenerator->generate($request->query->all());
        $title = $this->movementReportGenerator->getTitle();
        $headers = $this->movementReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/resignation-return.{_format}', name: 'inventory_report_resignation_return', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function resignationReturn(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->resignationReturnReportGenerator->generate($request->query->all());
        $title = $this->resignationReturnReportGenerator->getTitle();
        $headers = $this->resignationReturnReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/physical-counts.{_format}', name: 'inventory_report_physical_counts', methods: ['GET'], requirements: ['_format' => 'xlsx'])]
    public function physicalCounts(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->physicalCountReportGenerator->generate($request->query->all());
        $title = $this->physicalCountReportGenerator->getTitle();
        $headers = $this->physicalCountReportGenerator->getHeaders();

        return $this->excelExport->export($title, $headers, $data);
    }
}
