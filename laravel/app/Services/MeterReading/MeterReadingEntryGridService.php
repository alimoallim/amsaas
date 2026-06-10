<?php

namespace App\Services\MeterReading;

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MeterReadingEntryGridService
{
    public function __construct(
        protected User $user,
    ) {
    }

    /**
     * @param  array{building_id?: string|null, utility_type?: string|null, reading_date: string, per_page?: int, page?: int}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $readingDate = $filters['reading_date'];
        $perPage = min(max((int) ($filters['per_page'] ?? 50), 1), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);

        $processor = new MeterReadingProcessorService($this->user);

        $query = Meter::query()
            ->operational()
            ->with([
                'apartment:id,unit_number,building_id',
                'tenant:id,display_name,first_name,last_name,company_name',
                'building:id,name,code',
            ])
            ->when(
                ! empty($filters['building_id']),
                fn (Builder $query) => $query->where('building_id', $filters['building_id'])
            )
            ->when(
                ! empty($filters['utility_type']),
                fn (Builder $query) => $query->where('utility_type', $filters['utility_type'])
            )
            ->orderByRaw(
                'COALESCE((SELECT unit_number FROM apartments WHERE apartments.id = meters.apartment_id), meters.meter_number) ASC'
            );

        $paginator = $query->paginate($perPage, page: $page);

        $meterIds = $paginator->getCollection()->pluck('id')->all();

        $existingReadings = MeterReading::query()
            ->where('company_id', $this->user->company_id)
            ->whereIn('meter_id', $meterIds)
            ->whereDate('reading_date', $readingDate)
            ->get()
            ->keyBy('meter_id');

        $paginator->getCollection()->transform(function (Meter $meter) use (
            $readingDate,
            $processor,
            $existingReadings,
        ) {
            $existing = $existingReadings->get($meter->id);

            return [
                'meter_id' => $meter->id,
                'meter_number' => $meter->meter_number,
                'utility_type' => $meter->utility_type,
                'unit_number' => $meter->apartment?->unit_number,
                'tenant_name' => $this->resolveTenantName($meter),
                'building_name' => $meter->building?->name,
                'previous_reading' => $processor->getPreviousReadingForDate(
                    $meter,
                    $readingDate,
                    $existing?->id,
                ),
                'average_consumption' => $processor->getAverageConsumption($meter),
                'existing_reading' => $existing ? [
                    'id' => $existing->id,
                    'current_reading' => Money::toScale((string) $existing->current_reading, 4),
                    'consumption' => Money::toScale((string) $existing->consumption, 4),
                    'status' => $existing->status,
                    'anomaly_detected' => (bool) $existing->anomaly_detected,
                    'can_edit' => $existing->canBeEdited(),
                ] : null,
            ];
        });

        return $paginator;
    }

    protected function resolveTenantName(Meter $meter): ?string
    {
        $tenant = $meter->tenant;

        if (! $tenant) {
            return null;
        }

        if (filled($tenant->display_name)) {
            return trim((string) $tenant->display_name);
        }

        if (filled($tenant->company_name)) {
            return trim((string) $tenant->company_name);
        }

        $personal = trim(implode(' ', array_filter([
            $tenant->first_name,
            $tenant->last_name,
        ])));

        return $personal !== '' ? $personal : null;
    }
}
