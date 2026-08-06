<?php

declare(strict_types=1);

namespace App\Expense\Presentation\Http;

use App\Expense\Application\Report\Advance\AdvanceReportGenerator;
use App\Expense\Application\Report\Expense\ExpenseReportGenerator;
use App\Shared\Infrastructure\Export\ExcelExportService;
use App\Shared\Infrastructure\Export\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/expense/report')]
#[IsGranted('expense.report')]
final class ExpenseReportController extends AbstractController
{
    public function __construct(
        private readonly ExpenseReportGenerator $expenseReportGenerator,
        private readonly AdvanceReportGenerator $advanceReportGenerator,
        private readonly ExcelExportService $excelExport,
        private readonly PdfExportService $pdfExport,
    ) {
    }

    #[Route('/expenses.{_format}', name: 'expense_report_expenses', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function expenses(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->expenseReportGenerator->generate($request->query->all());
        $title = $this->expenseReportGenerator->getTitle();
        $headers = $this->expenseReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/advances.{_format}', name: 'expense_report_advances', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function advances(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->advanceReportGenerator->generate($request->query->all());
        $title = $this->advanceReportGenerator->getTitle();
        $headers = $this->advanceReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }
}
