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

            'billing_runs',

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
                | Identification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'run_number',
                    100
                );

                $table->string(
                    'name',
                    255
                );

                $table->text(
                    'description'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Billing Cycle
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'billing_frequency',
                    50
                );

                $table->date(
                    'billing_period_start'
                );

                $table->date(
                    'billing_period_end'
                );

                /*
                |--------------------------------------------------------------------------
                | Scheduling & Execution
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'scheduled_at'
                )->nullable();

                $table->timestamp(
                    'execution_started_at'
                )->nullable();

                $table->timestamp(
                    'execution_completed_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Processing Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status',
                    30
                )->default('draft');

                /*
                |--------------------------------------------------------------------------
                | Processing Metrics
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'total_agreements_processed'
                )->default(0);

                $table->unsignedInteger(
                    'total_billing_items_generated'
                )->default(0);

                $table->unsignedInteger(
                    'total_successful_items'
                )->default(0);

                $table->unsignedInteger(
                    'total_failed_items'
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Financial Totals
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'subtotal_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'tax_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'penalty_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'discount_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'total_amount',
                    18,
                    2
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Currency
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'currency',
                    10
                )->default('USD');

                /*
                |--------------------------------------------------------------------------
                | Error Tracking
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'failure_count'
                )->default(0);

                $table->unsignedInteger(
                    'success_count'
                )->default(0);

                $table->longText(
                    'error_summary'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Metadata
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'metadata'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Execution Controls
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_dry_run'
                )->default(false);

                $table->boolean(
                    'is_locked'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Execution Audit
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'executed_by'
                )->nullable();

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
                    'executed_by'
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

                    'company_id',

                    'run_number',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Enterprise Performance Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'billing_frequency',
                ]);

                $table->index([

                    'company_id',

                    'billing_period_start',
                ]);

                $table->index([

                    'company_id',

                    'billing_period_end',
                ]);

                $table->index([

                    'company_id',

                    'execution_started_at',
                ]);

                $table->index([

                    'company_id',

                    'execution_completed_at',
                ]);

                $table->index([

                    'company_id',

                    'is_locked',
                ]);

                $table->index([

                    'company_id',

                    'is_dry_run',
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
            'billing_runs'
        );
    }
};