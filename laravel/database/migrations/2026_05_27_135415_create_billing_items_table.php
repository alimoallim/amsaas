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

            'billing_items',

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
                | Financial Relationships
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'agreement_id'
                );

                $table->uuid(
                    'agreement_charge_id'
                );

                $table->uuid(
                    'charge_model_id'
                );

                $table->uuid(
                    'charge_type_id'
                );

                $table->uuid(
                    'billing_run_id'
                )->nullable();

                $table->uuid(
                    'invoice_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Billing Period
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'billing_period_start'
                );

                $table->date(
                    'billing_period_end'
                );

                $table->date(
                    'billing_date'
                );

                $table->date(
                    'due_date'
                );

                /*
                |--------------------------------------------------------------------------
                | Billing Calculation
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'quantity',
                    18,
                    4
                )->default(1);

                $table->decimal(
                    'unit_rate',
                    18,
                    6
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Financial Amounts
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'base_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'tax_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'discount_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'penalty_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'adjustment_amount',
                    18,
                    2
                )->default(0);

                $table->decimal(
                    'subtotal_amount',
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
                | Description
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'description'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Billing Calculation Snapshot
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'calculation_snapshot'
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
                | Generation Tracking
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'generated_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status',
                    30
                )->default('draft');

                /*
                |--------------------------------------------------------------------------
                | Posting Flags
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'posted_to_invoice'
                )->default(false);

                $table->boolean(
                    'posted_to_ledger'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Audit Trail
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
                    'agreement_id'
                )
                ->references('id')
                ->on('agreements')
                ->cascadeOnDelete();

                $table->foreign(
                    'agreement_charge_id'
                )
                ->references('id')
                ->on('agreement_charges')
                ->cascadeOnDelete();

                $table->foreign(
                    'charge_model_id'
                )
                ->references('id')
                ->on('charge_models')
                ->cascadeOnDelete();

                $table->foreign(
                    'charge_type_id'
                )
                ->references('id')
                ->on('charge_types')
                ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Optional Relations
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'billing_run_id'
                )
                ->references('id')
                ->on('billing_runs')
                ->nullOnDelete();

                $table->foreign(
                    'invoice_id'
                )
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | User Audit
                |--------------------------------------------------------------------------
                */

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

                    'agreement_charge_id',

                    'billing_period_start',

                    'billing_period_end',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Enterprise Financial Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'agreement_id',
                ]);

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'billing_date',
                ]);

                $table->index([

                    'company_id',

                    'due_date',
                ]);

                $table->index([

                    'company_id',

                    'invoice_id',
                ]);

                $table->index([

                    'company_id',

                    'billing_run_id',
                ]);

                $table->index([

                    'company_id',

                    'posted_to_invoice',
                ]);

                $table->index([

                    'company_id',

                    'posted_to_ledger',
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
            'billing_items'
        );
    }
};