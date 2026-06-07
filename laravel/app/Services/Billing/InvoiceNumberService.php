<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\InvoiceNumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceNumberService
{
    /**
     * Allocate the next sequential, company-prefixed invoice number for a billing year.
     * Numbers are never reused, including after void.
     */
    public function next(string $companyId, ?int $year = null): string
    {
        $year = $year ?? (int) now()->format('Y');

        return DB::transaction(function () use ($companyId, $year) {
            $sequence = InvoiceNumberSequence::query()
                ->where('company_id', $companyId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = InvoiceNumberSequence::create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'year' => $year,
                    'last_number' => 1,
                ]);

                $next = 1;
            } else {
                $next = $sequence->last_number + 1;
                $sequence->update(['last_number' => $next]);
            }

            $prefix = $this->resolvePrefix($companyId);

            return sprintf('%s-%d-%05d', $prefix, $year, $next);
        });
    }

    protected function resolvePrefix(string $companyId): string
    {
        $company = Company::query()->find($companyId);
        $firstWord = preg_split('/\s+/', trim((string) ($company?->name ?? '')))[0] ?? '';
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $firstWord));

        if ($raw === '') {
            return 'INV';
        }

        return substr($raw, 0, 6);
    }
}
