<?php

namespace App\Services\MeterReading;

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

            $consumption =
                $this->calculateConsumption(

                    previousReading:
                        $previousReading,

                    currentReading:
                        $data['current_reading']
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
                        $data['current_reading'],

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

        $reading->update([

            'status' =>
                MeterReading::STATUS_APPROVED,

            'approved_by' =>
                $this->user->id,

            'approved_at' =>
                now(),
        ]);

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

    ): float {

        $latestReading =
            $meter->readings()

                ->latest(
                    'reading_date'
                )

                ->first();

        if (
            $latestReading
        ) {

            return (float)

                $latestReading
                    ->current_reading;
        }

        return (float)

            $meter
                ->initial_reading;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Consumption
    |--------------------------------------------------------------------------
    */

    protected function calculateConsumption(

        float $previousReading,

        float $currentReading

    ): float {

        if (
            $currentReading
            <
            $previousReading
        ) {

            throw ValidationException::withMessages([

                'current_reading' => [

                    'Current reading cannot be less than previous reading.',
                ],
            ]);
        }

        return round(

            $currentReading
            -
            $previousReading,

            4
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Detect Operational Anomalies
    |--------------------------------------------------------------------------
    */

    protected function detectAnomalies(

        Meter $meter,

        float $previousReading,

        float $currentReading,

        float $consumption

    ): array {

        /*
        |--------------------------------------------------------------------------
        | Negative Consumption
        |--------------------------------------------------------------------------
        */

        if (
            $consumption < 0
        ) {

            return [

                'detected' => true,

                'reason' =>
                    'Negative consumption detected.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Zero Consumption
        |--------------------------------------------------------------------------
        */

        if (
            $consumption == 0
        ) {

            return [

                'detected' => true,

                'reason' =>
                    'Zero utility consumption detected.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Historical Average Analysis
        |--------------------------------------------------------------------------
        */

        $averageConsumption =
            $this->calculateAverageConsumption(
                $meter
            );

        /*
        |--------------------------------------------------------------------------
        | Spike Detection
        |--------------------------------------------------------------------------
        */

        if (

            $averageConsumption > 0
            &&

            $consumption >
            (
                $averageConsumption * 3
            )
        ) {

            return [

                'detected' => true,

                'reason' =>
                    'Abnormal consumption spike detected.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Sudden Drop Detection
        |--------------------------------------------------------------------------
        */

        if (

            $averageConsumption > 0
            &&

            $consumption <
            (
                $averageConsumption * 0.10
            )
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

    ): float {

        return round(

            (float)

            $meter->readings()

                ->where(

                    'status',

                    MeterReading::STATUS_APPROVED
                )

                ->latest(
                    'reading_date'
                )

                ->limit(6)

                ->avg(
                    'consumption'
                ),

            4
        );
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