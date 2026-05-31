<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */

    public function up(): void
    {
        Schema::create(

            'meter_readings',

            function (
                Blueprint $table
            ) {

                /*
                |--------------------------------------------------------------------------
                | Primary Key
                |--------------------------------------------------------------------------
                */

                $table->uuid('id')
                    ->primary();

                /*
                |--------------------------------------------------------------------------
                | Multi-Tenant Isolation
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'company_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Meter Relationships
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'meter_id'
                );

                $table->uuid(
                    'building_id'
                )->nullable();

                $table->uuid(
                    'apartment_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Reading Information
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'reading_date'
                );

                $table->decimal(
                    'previous_reading',
                    18,
                    4
                );

                $table->decimal(
                    'current_reading',
                    18,
                    4
                );

                $table->decimal(
                    'consumption',
                    18,
                    4
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Reading Classification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'reading_type',
                    50
                )->default('actual');

                $table->string(
                    'reading_source',
                    50
                )->default('manual');

                /*
                |--------------------------------------------------------------------------
                | Reader Information
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'reader_name',
                    255
                )->nullable();

                $table->uuid(
                    'reader_user_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Approval Workflow
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status',
                    50
                )->default('draft');

                /*
                |--------------------------------------------------------------------------
                | Anomaly Detection
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'anomaly_detected'
                )->default(false);

                $table->text(
                    'anomaly_reason'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Operational Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Evidence Attachments
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'attachment_path'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Extensible Metadata
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'metadata'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Approval Audit
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'approved_by'
                )->nullable();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | User Audit
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'created_by'
                )->nullable();

                $table->uuid(
                    'updated_by'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'company_id'
                )
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

                $table->foreign(
                    'meter_id'
                )
                ->references('id')
                ->on('meters')
                ->cascadeOnDelete();

                $table->foreign(
                    'building_id'
                )
                ->references('id')
                ->on('buildings')
                ->nullOnDelete();

                $table->foreign(
                    'apartment_id'
                )
                ->references('id')
                ->on('apartments')
                ->nullOnDelete();

                $table->foreign(
                    'reader_user_id'
                )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

                $table->foreign(
                    'approved_by'
                )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

                $table->foreign(
                    'created_by'
                )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

                $table->foreign(
                    'updated_by'
                )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Enterprise Constraints
                |--------------------------------------------------------------------------
                */

                $table->unique([

                    'meter_id',

                    'reading_date',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Enterprise Performance Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'meter_id',
                ]);

                $table->index([

                    'company_id',

                    'reading_date',
                ]);

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'reading_type',
                ]);

                $table->index([

                    'company_id',

                    'anomaly_detected',
                ]);

                $table->index([

                    'company_id',

                    'approved_at',
                ]);
            }
        );
    }

    /**
     * Reverse migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists(
            'meter_readings'
        );
    }
};