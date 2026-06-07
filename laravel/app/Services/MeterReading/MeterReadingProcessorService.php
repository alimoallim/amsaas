<?php

namespace App\Services\MeterReading;

use App\Support\Money;
use App\Services\Billing\GenerateChargeService;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class MeterReadingProcessorService
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(

        protected User $user
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Process Meter Reading
    |--------------------------------------------------------------------------
    */

    public function process(

        array $data

    ): MeterReading {

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Retrieve Meter
            |--------------------------------------------------------------------------
            */

            $meter =
                Meter::query()

                    ->where(
                        'company_id',
                        $this->user->company_id
                    )

                    ->findOrFail(
                        $data['meter_id']
                    );

            /*
            |--------------------------------------------------------------------------
            | Validate Meter Operational Status
            |--------------------------------------------------------------------------
            */

            $this->validateMeterStatus(
                $meter
            );

            /*
            |--------------------------------------------------------------------------
            | Resolve Previous Reading
            |--------------------------------------------------------------------------
            */

            $previousReading =
                $this->resolvePreviousReading(
                    meter: $meter
                );

            /*
            |--------------------------------------------------------------------------
            | Resolve Consumption
            |--------------------------------------------------------------------------
            */

            $currentReading = Money::toScale((string) $data['current_reading'], 4);

            $consumption =
                $this->calculateConsumption(

                    previousReading:
                        $previousReading,

                    currentReading:
                        $currentReading
                );

            /*
            |--------------------------------------------------------------------------
            | Detect Anomalies
            |--------------------------------------------------------------------------
            */

            $anomaly =
                $this->detectAnomalies(

                    meter:
                        $meter,

                    previousReading:
                        $previousReading,

                    currentReading:
                        $currentReading,

                    consumption:
                        $consumption
                );

            /*
            |--------------------------------------------------------------------------
            | Create Reading
            |--------------------------------------------------------------------------
            */

            $reading =
                MeterReading::create([

                    'id' =>
                        str()->uuid(),

                    'company_id' =>
                        $this->user->company_id,

                    'meter_id' =>
                        $meter->id,

                    'building_id' =>
                        $meter->building_id,

                    'apartment_id' =>
                        $meter->apartment_id,

                    'reading_date' =>
                        Carbon::parse(
                            $data['reading_date']
                        ),

                    'previous_reading' =>
                        $previousReading,

                    'current_reading' =>
                        $data['current_reading'],

                    'consumption' =>
                        $consumption,

                    'reading_type' =>
                        $data['reading_type']
                        ??
                        MeterReading::TYPE_ACTUAL,

                    'reading_source' =>
                        $data['reading_source']
                        ??
                        MeterReading::SOURCE_MANUAL,

                    'reader_name' =>
                        $data['reader_name']
                        ??
                        $this->user->name,

                    'reader_user_id' =>
                        $this->user->id,

                    'status' =>

                        $anomaly['detected']

                        ? MeterReading::STATUS_DRAFT

                        : MeterReading::STATUS_VERIFIED,

                    'anomaly_detected' =>
                        $anomaly['detected'],

                    'anomaly_reason' =>
                        $anomaly['reason'],

                    'notes' =>
                        $data['notes']
                        ?? null,

                    'metadata' =>
                        $data['metadata']
                        ?? null,

                    'created_by' =>
                        $this->user->id,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Sync Meter
            |--------------------------------------------------------------------------
            */

            $this->syncMeter(

                meter:
                    $meter,

                reading:
                    $reading
            );

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            Log::info(

                'Meter reading processed successfully.',

                [

                    'meter_id' =>
                        $meter->id,

                    'meter_number' =>
                        $meter->meter_number,

                    'reading_id' =>
                        $reading->id,

                    'consumption' =>
                        $consumption,

                    'anomaly_detected' =>
                        $anomaly['detected'],
                ]
            );

            DB::commit();

            return $reading;
        }

        catch (Throwable $exception) {

            DB::rollBack();

            Log::critical(

                'Meter reading processing failed.',

                [

                    'message' =>
                        $exception->getMessage(),

                    'trace' =>
                        $exception->getTraceAsString(),

                    'user_id' =>
                        $this->user->id,
                ]
            );

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Reading
    |--------------------------------------------------------------------------
    */
public function approve(
    MeterReading $reading
): MeterReading {

    if (
        $reading->isApproved()
    ) {

        throw ValidationException::withMessages([

            'reading' => [

                'Meter reading already approved.',
            ],
        ]);
    }

    if (! $reading->canBeApproved()) {
        throw ValidationException::withMessages([
            'reading' => [
                'Only verified readings can be approved. Resolve anomalies or re-capture the reading first.',
            ],
        ]);
    }

    DB::transaction(

        function () use (
            $reading
        ) {

            $reading->update([

                'status' =>
                    MeterReading::STATUS_APPROVED,

                'approved_by' =>
                    $this->user->id,

                'approved_at' =>
                    now(),
            ]);
            

            app(
                GenerateChargeService::class
            )->generateFromMeterReading(
                $reading
            );
        }
    );
    

    return $reading->fresh();

}

    /*
    |--------------------------------------------------------------------------
    | Reject Reading
    |--------------------------------------------------------------------------
    */

    public function reject(

        MeterReading $reading,

        ?string $reason = null

    ): MeterReading {

        $reading->update([

            'status' =>
                MeterReading::STATUS_REJECTED,

            'notes' =>
                $reason
                ??
                $reading->notes,
        ]);

        return $reading->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Meter Status
    |--------------------------------------------------------------------------
    */

    protected function validateMeterStatus(

        Meter $meter

    ): void {

        if (
            !$meter->isOperational()
        ) {

            throw ValidationException::withMessages([

                'meter' => [

                    'Meter is not operational.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Previous Reading
    |--------------------------------------------------------------------------
    */

    protected function resolvePreviousReading(

        Meter $meter

    ): string {

        $latestReading =
            $meter->readings()

                ->latest(
                    'reading_date'
                )

                ->first();

        if (
            $latestReading
        ) {

            return Money::toScale((string) $latestReading->current_reading, 4);
        }

        return Money::toScale((string) $meter->initial_reading, 4);
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Consumption
    |--------------------------------------------------------------------------
    */

    protected function calculateConsumption(

        string $previousReading,

        string $currentReading

    ): string {

        if (Money::comp($currentReading, $previousReading) < 0) {

            throw ValidationException::withMessages([

                'current_reading' => [

                    'Current reading cannot be less than previous reading.',
                ],
            ]);
        }

        return Money::toScale(Money::sub($currentReading, $previousReading), 4);
    }

    /*
    |--------------------------------------------------------------------------
    | Detect Operational Anomalies
    |--------------------------------------------------------------------------
    */

    protected function detectAnomalies(

        Meter $meter,

        string $previousReading,

        string $currentReading,

        string $consumption

    ): array {

        if (Money::comp($consumption, '0') < 0) {

            return [

                'detected' => true,

                'reason' =>
                    'Negative consumption detected.',
            ];
        }

        if (Money::comp($consumption, '0') === 0) {

            return [

                'detected' => true,

                'reason' =>
                    'Zero utility consumption detected.',
            ];
        }

        $averageConsumption =
            $this->calculateAverageConsumption(
                $meter
            );

        if (
            Money::comp($averageConsumption, '0') > 0
            && Money::comp($consumption, Money::mul($averageConsumption, '3')) > 0
        ) {

            return [

                'detected' => true,

                'reason' =>
                    'Abnormal consumption spike detected.',
            ];
        }

        if (
            Money::comp($averageConsumption, '0') > 0
            && Money::comp($consumption, Money::mul($averageConsumption, '0.10')) < 0
        ) {

            return [

                'detected' => true,

                'reason' =>
                    'Abnormally low consumption detected.',
            ];
        }

        return [

            'detected' => false,

            'reason' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Average Consumption
    |--------------------------------------------------------------------------
    */

    protected function calculateAverageConsumption(

        Meter $meter

    ): string {

        $average = $meter->readings()
            ->where('status', MeterReading::STATUS_APPROVED)
            ->latest('reading_date')
            ->limit(6)
            ->avg('consumption');

        return Money::toScale((string) ($average ?? '0'), 4);
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Meter Operational State
    |--------------------------------------------------------------------------
    */

    protected function syncMeter(

        Meter $meter,

        MeterReading $reading

    ): void {

        $meter->update([

            'current_reading' =>
                $reading->current_reading,

            'last_reading_at' =>
                $reading->reading_date,

            'updated_by' =>
                $this->user->id,
        ]);
    }
}