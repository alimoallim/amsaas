<?php

namespace App\Services;

use App\Models\Meter;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;

class MeterLifecycleService
{
    /*
    |--------------------------------------------------------------------------
    | Activate Meter
    |--------------------------------------------------------------------------
    */

    public function activate(
        Meter $meter
    ): Meter {

        if (
            $meter->isDecommissioned()
        ) {

            throw ValidationException::withMessages([

                'meter' =>

                    'Decommissioned meters cannot be reactivated.',
            ]);
        }

        return DB::transaction(

            function () use ($meter) {

                $meter->update([

                    'status' =>

                        Meter::STATUS_ACTIVE,

                    'maintenance_required' =>

                        false,
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Inactive
    |--------------------------------------------------------------------------
    */

    public function deactivate(
        Meter $meter,
        ?string $reason = null
    ): Meter {

        return DB::transaction(

            function () use (
                $meter,
                $reason
            ) {

                $meter->update([

                    'status' =>

                        Meter::STATUS_INACTIVE,

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $meter->notes,

                            message:
                                'Meter deactivated: '
                                . $reason
                        ),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Faulty
    |--------------------------------------------------------------------------
    */

    public function markFaulty(
        Meter $meter,
        ?string $reason = null
    ): Meter {

        return DB::transaction(

            function () use (
                $meter,
                $reason
            ) {

                $meter->update([

                    'status' =>

                        Meter::STATUS_FAULTY,

                    'maintenance_required' =>

                        true,

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $meter->notes,

                            message:
                                'Meter marked faulty: '
                                . $reason
                        ),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Put Under Maintenance
    |--------------------------------------------------------------------------
    */

    public function markUnderMaintenance(
        Meter $meter,
        ?string $reason = null
    ): Meter {

        return DB::transaction(

            function () use (
                $meter,
                $reason
            ) {

                $meter->update([

                    'status' =>

                        Meter::STATUS_UNDER_MAINTENANCE,

                    'maintenance_required' =>

                        true,

                    'last_maintenance_at' =>

                        now(),

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $meter->notes,

                            message:
                                'Meter under maintenance: '
                                . $reason
                        ),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Maintenance
    |--------------------------------------------------------------------------
    */

    public function completeMaintenance(
        Meter $meter,
        ?string $note = null
    ): Meter {

        return DB::transaction(

            function () use (
                $meter,
                $note
            ) {

                $meter->update([

                    'status' =>

                        Meter::STATUS_ACTIVE,

                    'maintenance_required' =>

                        false,

                    'last_maintenance_at' =>

                        now(),

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $meter->notes,

                            message:
                                'Maintenance completed: '
                                . $note
                        ),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Decommission Meter
    |--------------------------------------------------------------------------
    */

    public function decommission(
        Meter $meter,
        ?string $reason = null
    ): Meter {

        if (
            $meter->isDecommissioned()
        ) {

            throw ValidationException::withMessages([

                'meter' =>

                    'Meter already decommissioned.',
            ]);
        }

        return DB::transaction(

            function () use (
                $meter,
                $reason
            ) {

                $meter->update([

                    'status' =>

                        Meter::STATUS_DECOMMISSIONED,

                    'decommissioned_at' =>

                        now(),

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $meter->notes,

                            message:
                                'Meter decommissioned: '
                                . $reason
                        ),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Replace Meter
    |--------------------------------------------------------------------------
    */

    public function replace(
        Meter $oldMeter,
        Meter $newMeter,
        ?string $reason = null
    ): array {

        if (
            $oldMeter->isDecommissioned()
        ) {

            throw ValidationException::withMessages([

                'meter' =>

                    'Cannot replace a decommissioned meter.',
            ]);
        }

        return DB::transaction(

            function () use (
                $oldMeter,
                $newMeter,
                $reason
            ) {

                /*
                |--------------------------------------------------------------------------
                | Archive Old Meter
                |--------------------------------------------------------------------------
                */

                $oldMeter->update([

                    'status' =>

                        Meter::STATUS_REPLACED,

                    'replacement_meter_id' =>

                        $newMeter->id,

                    'decommissioned_at' =>

                        now(),

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $oldMeter->notes,

                            message:
                                'Meter replaced by '
                                . $newMeter->meter_number
                                . '. Reason: '
                                . $reason
                        ),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Activate Replacement Meter
                |--------------------------------------------------------------------------
                */

                $newMeter->update([

                    'status' =>

                        Meter::STATUS_ACTIVE,

                    'maintenance_required' =>

                        false,
                ]);

                return [

                    'old_meter' =>

                        $oldMeter->fresh(),

                    'new_meter' =>

                        $newMeter->fresh(),
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Completed
    |--------------------------------------------------------------------------
    */

    public function completeInspection(
        Meter $meter,
        ?string $note = null
    ): Meter {

        return DB::transaction(

            function () use (
                $meter,
                $note
            ) {

                $meter->update([

                    'last_inspected_at' =>

                        now(),

                    'inspection_due_date' =>

                        now()->addYear(),

                    'notes' =>

                        $this->appendOperationalNote(

                            existing:
                                $meter->notes,

                            message:
                                'Inspection completed: '
                                . $note
                        ),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Current Reading
    |--------------------------------------------------------------------------
    */

    public function updateCurrentReading(
        Meter $meter,
        float $reading
    ): Meter {

        if (
            !$meter->canReceiveReadings()
        ) {

            throw ValidationException::withMessages([

                'meter' =>

                    'Meter cannot receive readings in current lifecycle status.',
            ]);
        }

        if (
            $reading < $meter->current_reading
        ) {

            throw ValidationException::withMessages([

                'reading' =>

                    'Current reading cannot be lower than previous reading.',
            ]);
        }

        return DB::transaction(

            function () use (
                $meter,
                $reading
            ) {

                $meter->update([

                    'current_reading' =>

                        $reading,

                    'last_reading_at' =>

                        now(),
                ]);

                return $meter->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Operational Note Helper
    |--------------------------------------------------------------------------
    */

    protected function appendOperationalNote(
        ?string $existing,
        ?string $message
    ): string {

        if (
            blank($message)
        ) {

            return (string)
                $existing;
        }

        $timestamp =
            now()->toDateTimeString();

        $formatted =
            "[{$timestamp}] {$message}";

        if (
            blank($existing)
        ) {

            return $formatted;
        }

        return $existing
            . PHP_EOL
            . $formatted;
    }
}