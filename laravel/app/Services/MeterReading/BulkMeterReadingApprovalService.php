<?php

namespace App\Services\MeterReading;

use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Throwable;

class BulkMeterReadingApprovalService
{
    public function __construct(
        protected User $user,
    ) {
    }

    /**
     * @param  array<int, string>  $readingIds
     * @return array{approved: int, skipped: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function approve(array $readingIds): array
    {
        $processor = new MeterReadingProcessorService($this->user);

        $approved = 0;
        $skipped = 0;
        $failed = 0;
        $results = [];

        $readings = MeterReading::query()
            ->where('company_id', $this->user->company_id)
            ->whereIn('id', $readingIds)
            ->get()
            ->keyBy('id');

        foreach ($readingIds as $index => $readingId) {
            $reading = $readings->get($readingId);

            if (! $reading) {
                $skipped++;
                $results[] = [
                    'index' => $index,
                    'reading_id' => $readingId,
                    'status' => 'skipped',
                    'message' => 'Reading not found.',
                ];

                continue;
            }

            if (! $reading->canBeApproved()) {
                $skipped++;
                $results[] = [
                    'index' => $index,
                    'reading_id' => $reading->id,
                    'status' => 'skipped',
                    'message' => 'Only verified readings can be approved.',
                ];

                continue;
            }

            try {
                $processor->approve($reading);
                $approved++;
                $results[] = [
                    'index' => $index,
                    'reading_id' => $reading->id,
                    'status' => 'approved',
                ];
            } catch (ValidationException $exception) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'reading_id' => $reading->id,
                    'status' => 'failed',
                    'message' => collect($exception->errors())->flatten()->first()
                        ?? 'Approval failed.',
                ];
            } catch (Throwable $exception) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'reading_id' => $reading->id,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'approved' => $approved,
            'skipped' => $skipped,
            'failed' => $failed,
            'results' => $results,
        ];
    }
}
