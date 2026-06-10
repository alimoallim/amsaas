<?php

namespace App\Services\Sales;

use App\Models\Company;
use App\Models\SaleAgreement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SaleCompletionCertificateService
{
    public function generate(SaleAgreement $sale): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::info('DomPDF not installed; skipping completion certificate generation.', [
                'sale_agreement_id' => $sale->id,
            ]);

            return null;
        }

        if (! view()->exists('sales.completion_certificate')) {
            Log::warning('Completion certificate template missing; skipping generation.', [
                'sale_agreement_id' => $sale->id,
            ]);

            return null;
        }

        $sale->loadMissing(['agreement.apartment.building', 'agreement.buyer']);
        $company = Company::query()->find($sale->agreement->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.completion_certificate', [
            'sale' => $sale,
            'agreement' => $sale->agreement,
            'company' => $company,
        ]);

        $path = "sales/{$sale->agreement->company_id}/{$sale->id}/completion_certificate.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
