<?php
namespace App\Console\Commands;
use App\Models\Building;
use App\Models\Apartment;
use App\Services\InvoiceGenerationService;
use Illuminate\Console\Command;
class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'billing:generate-monthly {--year=} {--month=}';
    protected $description = 'Generate monthly invoices for all apartments across all buildings.';
    public function handle(InvoiceGenerationService $service): void
    {
        $year = $this->option('year') ?? now()->year;
        $month = $this->option('month') ?? now()->month;

        $this->info("Starting invoice generation for {$month}/{$year}...");

        Building::all()->each(function ($building) use ($service, $year, $month) {
            $this->info("Processing Building: {$building->name} ({$building->currency_code})");

            $building->apartments->each(function ($apartment) use ($service, $year, $month) {
                $service->generateForApartment($apartment, $year, $month);
            });
        });

        $this->info('All invoices have been generated successfully.');
    }
}