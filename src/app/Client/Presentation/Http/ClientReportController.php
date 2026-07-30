<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Report\ContractsExpiring\ContractsExpiringReportGenerator;
use App\Shared\Infrastructure\Export\ExcelExportService;
use App\Shared\Infrastructure\Export\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/report')]
#[IsGranted('client.report')]
final class ClientReportController extends AbstractController
{
    public function __construct(
        private readonly ContractsExpiringReportGenerator $contractsExpiringReportGenerator,
        private readonly ExcelExportService $excelExport,
        private readonly PdfExportService $pdfExport,
    ) {}

    #[Route('/contracts-expiring.{_format}', name: 'client_report_contracts_expiring', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function contractsExpiring(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->contractsExpiringReportGenerator->generate($request->query->all());
        $title = $this->contractsExpiringReportGenerator->getTitle();
        $headers = $this->contractsExpiringReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }
}
