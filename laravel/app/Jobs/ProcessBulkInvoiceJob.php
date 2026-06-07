<?php

namespace App\Jobs;

use App\Jobs\Concerns\InitializesTenantContext;
use App\Models\Apartment;
use App\Services\InvoiceGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessBulkInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InitializesTenantContext;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        string $companyId,
        public array $apartments,
        public int $year,
        public int $month,
        public int $userId,
    ) {
        $this->companyId = $companyId;
    }

    public function handle(InvoiceGenerationService $generator): void
    {
        $this->initializeTenantContext();

        Cache::put('batch_status_'.$this->userId, 'processing', 3600);

        foreach ($this->apartments as $aptData) {
            try {
                $apartment = Apartment::query()
                    ->where('company_id', $this->companyId)
                    ->find($aptData['id'] ?? null);

                if (! $apartment) {
                    Log::warning('Bulk invoice skipped: apartment not in tenant scope.', [
                        'apartment_id' => $aptData['id'] ?? null,
                        'company_id' => $this->companyId,
                    ]);
                    continue;
                }

                $generator->generateForApartment($apartment, $this->year, $this->month);
            } catch (\Throwable $e) {
                Log::error('Bulk invoice generation failed for apartment.', [
                    'apartment_id' => $aptData['id'] ?? null,
                    'company_id' => $this->companyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Cache::put('batch_status_'.$this->userId, 'completed', 3600);
    }
}
