<?php

namespace App\Services\MeterReading;

use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Throwable;

class BulkMeterReadingService
{
    public function __construct(
        protected User $user,
    ) {
    }

    /**
     * @param  array<int, array{meter_id: string, current_reading?: mixed, notes?: string|null}>  $readings
     * @return array{saved: int, skipped: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function store(string $readingDate, array $readings): array
    {
        $processor = new MeterReadingProcessorService($this->user);

        $saved = 0;
        $skipped = 0;
        $failed = 0;
        $results = [];

        foreach ($readings as $index => $row) {
            $meterId = (string) ($row['meter_id'] ?? '');

            if ($meterId === '') {
                $skipped++;
                $results[] = [
                    'index' => $index,
                    'meter_id' => null,
                    'status' => 'skipped',
                    'message' => 'Missing meter_id.',
                ];

                continue;
            }

            if ($this->shouldSkipReading($row['current_reading'] ?? null)) {
                $skipped++;
                $results[] = [
                    'index' => $index,
                    'meter_id' => $meterId,
                    'status' => 'skipped',
                    'message' => 'Empty reading skipped.',
                ];

                continue;
            }

            try {
                $existing = MeterReading::query()
                    ->where('company_id', $this->user->company_id)
                    ->where('meter_id', $meterId)
                    ->whereDate('reading_date', $readingDate)
                    ->first();

                $payload = [
                    'meter_id' => $meterId,
                    'reading_date' => $readingDate,
                    'current_reading' => $row['current_reading'],
                    'reading_source' => MeterReading::SOURCE_MANUAL,
                    'notes' => $row['notes'] ?? null,
                ];

                if ($existing) {
                    if (! $existing->canBeEdited()) {
                        $failed++;
                        $results[] = [
                            'index' => $index,
                            'meter_id' => $meterId,
                            'status' => 'failed',
                            'message' => 'A reading for this meter on this date already exists and cannot be edited.',
                            'reading_id' => $existing->id,
                        ];

                        continue;
                    }

                    $reading = $processor->update($existing, $payload);
                } else {
                    $reading = $processor->process($payload);
                }

                $saved++;
                $results[] = [
                    'index' => $index,
                    'meter_id' => $meterId,
                    'status' => 'saved',
                    'reading_id' => $reading->id,
                    'consumption' => $reading->consumption,
                    'anomaly_detected' => (bool) $reading->anomaly_detected,
                ];
            } catch (ValidationException $exception) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'meter_id' => $meterId,
                    'status' => 'failed',
                    'message' => collect($exception->errors())->flatten()->first()
                        ?? 'Validation failed.',
                    'errors' => $exception->errors(),
                ];
            } catch (Throwable $exception) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'meter_id' => $meterId,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'saved' => $saved,
            'skipped' => $skipped,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    protected function shouldSkipReading(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return false;
    }
}
