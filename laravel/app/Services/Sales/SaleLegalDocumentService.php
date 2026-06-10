<?php

namespace App\Services\Sales;

use App\Models\Company;
use App\Models\SaleAgreement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SaleLegalDocumentService
{
    public function generateCompletionCertificate(SaleAgreement $sale): ?string
    {
        return $this->renderPdf(
            $sale,
            'sales.completion_certificate',
            'completion_certificate.pdf',
            [
                'sale' => $sale,
                'agreement' => $sale->agreement,
                'company' => $this->company($sale),
            ],
        );
    }

    public function generateOwnershipTransferCertificate(SaleAgreement $sale): ?string
    {
        return $this->renderPdf(
            $sale,
            'sales.ownership_transfer_certificate',
            'ownership_transfer_certificate.pdf',
            [
                'sale' => $sale,
                'agreement' => $sale->agreement,
                'company' => $this->company($sale),
            ],
        );
    }

    public function generateSalesContract(SaleAgreement $sale): ?string
    {
        return $this->renderPdf(
            $sale,
            'sales.sales_contract',
            'sales_contract.pdf',
            $this->salesContractViewData($sale),
        );
    }

    public function salesContractBinary(SaleAgreement $sale): ?string
    {
        $sale->loadMissing(['agreement.apartment.building', 'agreement.buyer']);

        return $this->renderPdfBinary(
            $sale,
            'sales.sales_contract',
            $this->salesContractViewData($sale),
        );
    }

    /** @return array<string, mixed> */
    private function salesContractViewData(SaleAgreement $sale): array
    {
        return [
            'sale' => $sale,
            'agreement' => $sale->agreement,
            'company' => $this->company($sale),
        ];
    }

    public function generatePaymentPlanStatement(SaleAgreement $sale): ?string
    {
        $sale->loadMissing(['agreement', 'paymentAllocations.payment']);

        return $this->renderPdf(
            $sale,
            'sales.payment_plan_statement',
            'payment_plan_statement.pdf',
            $this->paymentPlanStatementViewData($sale),
        );
    }

    public function paymentPlanStatementBinary(SaleAgreement $sale): ?string
    {
        $sale->loadMissing(['agreement.apartment.building', 'agreement.buyer', 'paymentAllocations.payment']);

        return $this->renderPdfBinary(
            $sale,
            'sales.payment_plan_statement',
            $this->paymentPlanStatementViewData($sale),
        );
    }

    /** @return array<string, mixed> */
    private function paymentPlanStatementViewData(SaleAgreement $sale): array
    {
        return [
            'sale' => $sale,
            'agreement' => $sale->agreement,
            'company' => $this->company($sale),
            'summary' => $sale->paymentPlanSummary(),
            'payments' => $sale->paymentAllocations,
        ];
    }

    /** @deprecated Use generatePaymentPlanStatement */
    public function generateInstallmentSchedule(SaleAgreement $sale, ?Collection $schedule = null): ?string
    {
        return $this->generatePaymentPlanStatement($sale);
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function renderPdf(SaleAgreement $sale, string $view, string $filename, array $viewData): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::info('DomPDF not installed; skipping sale document generation.', [
                'sale_agreement_id' => $sale->id,
                'view' => $view,
            ]);

            return null;
        }

        if (! view()->exists($view)) {
            Log::warning('Sale document template missing.', [
                'sale_agreement_id' => $sale->id,
                'view' => $view,
            ]);

            return null;
        }

        $output = $this->renderPdfBinary($sale, $view, $viewData);
        if ($output === null) {
            return null;
        }

        $path = "sales/{$sale->agreement->company_id}/{$sale->id}/{$filename}";
        Storage::disk('local')->put($path, $output);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function renderPdfBinary(SaleAgreement $sale, string $view, array $viewData): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::warning('DomPDF not installed; cannot render sale document.', [
                'sale_agreement_id' => $sale->id,
                'view' => $view,
            ]);

            return null;
        }

        if (! view()->exists($view)) {
            Log::warning('Sale document template missing.', [
                'sale_agreement_id' => $sale->id,
                'view' => $view,
            ]);

            return null;
        }

        $sale->loadMissing(['agreement.apartment.building', 'agreement.buyer']);

        try {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $viewData)->output();
        } catch (\Throwable $exception) {
            Log::error('Sale document PDF rendering failed.', [
                'sale_agreement_id' => $sale->id,
                'view' => $view,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function company(SaleAgreement $sale): ?Company
    {
        return Company::query()->find($sale->agreement->company_id);
    }
}
