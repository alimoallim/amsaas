<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(

            'meters',

            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Primary Key
                |--------------------------------------------------------------------------
                */

                $table->uuid('id')
                    ->primary();

                /*
                |--------------------------------------------------------------------------
                | Multi-Tenancy
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid('company_id')

                    ->constrained()

                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Hierarchy Relationships
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid('building_id')

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->foreignUuid('apartment_id')

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                $table->foreignUuid('tenant_id')

                    ->nullable()

                    ->constrained()

                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Meter Lifecycle Relationships
                |--------------------------------------------------------------------------
                */

              $table->uuid(
    'replacement_meter_id'
)->nullable();

                /*
                |--------------------------------------------------------------------------
                | Operational Identity
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'meter_number'
                )

                ->unique();

                $table->string(
                    'serial_number'
                )

                ->nullable()

                ->index();

                /*
                |--------------------------------------------------------------------------
                | Utility Classification
                |--------------------------------------------------------------------------
                */

                $table->enum(

                    'utility_type',

                    [

                        'electricity',

                        'water',

                        'gas',

                        'steam',

                        'internet',

                        'solar',

                        'chilled_water',
                    ]
                )

                ->index();

                /*
                |--------------------------------------------------------------------------
                | Ownership Type
                |--------------------------------------------------------------------------
                */

                $table->enum(

                    'ownership_type',

                    [

                        'apartment',

                        'building',

                        'shared',

                        'tenant',
                    ]
                )

                ->default(
                    'apartment'
                )

                ->index();

                /*
                |--------------------------------------------------------------------------
                | Meter Type
                |--------------------------------------------------------------------------
                */

                $table->enum(

                    'meter_type',

                    [

                        'analog',

                        'digital',

                        'smart',
                    ]
                )

                ->default(
                    'digital'
                )

                ->index();

                /*
                |--------------------------------------------------------------------------
                | Measurement
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'measurement_unit',
                    50
                );

                $table->decimal(

                    'initial_reading',

                    20,

                    4
                )

                ->default(0);

                $table->decimal(

                    'current_reading',

                    20,

                    4
                )

                ->default(0);

                $table->decimal(

                    'multiplier_factor',

                    12,

                    4
                )

                ->default(1);

                $table->timestamp(
                    'last_reading_at'
                )

                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Lifecycle Dates
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'installation_date'
                )

                ->nullable();

                $table->timestamp(
                    'decommissioned_at'
                )

                ->nullable();

                $table->date(
                    'inspection_due_date'
                )

                ->nullable();

                $table->timestamp(
                    'last_maintenance_at'
                )

                ->nullable();

                $table->timestamp(
                    'last_inspected_at'
                )

                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Operational Status
                |--------------------------------------------------------------------------
                */

                $table->enum(

                    'status',

                    [

                        'active',

                        'inactive',

                        'faulty',

                        'under_maintenance',

                        'replaced',

                        'decommissioned',
                    ]
                )

                ->default('active')

                ->index();

                /*
                |--------------------------------------------------------------------------
                | Operational Flags
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_shared'
                )

                ->default(false);

                $table->boolean(
                    'supports_remote_reading'
                )

                ->default(false);

                $table->boolean(
                    'maintenance_required'
                )

                ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Manufacturer Information
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'manufacturer'
                )

                ->nullable();

                $table->string(
                    'model_number'
                )

                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Location Information
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'location_description'
                )

                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Operational Notes
                |--------------------------------------------------------------------------
                */

                $table->longText(
                    'notes'
                )

                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Flexible Metadata
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'metadata'
                )

                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit Fields
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid(
                    'created_by'
                )

                ->nullable()

                ->constrained(
                    'users'
                )

                ->nullOnDelete();

                $table->foreignUuid(
                    'updated_by'
                )

                ->nullable()

                ->constrained(
                    'users'
                )

                ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Performance Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'building_id',
                ]);

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'utility_type',
                ]);

                $table->index([

                    'company_id',

                    'ownership_type',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'meters'
        );
    }
};