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

            'agreement_charges',

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
                | Relationships
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'agreement_id'
                );

                $table->uuid(
                    'charge_model_id'
                );

                $table->uuid(
                    'charge_type_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Charge Customization
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'custom_name',
                    255
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Financial Overrides
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'override_amount',
                    18,
                    2
                )->nullable();

                $table->decimal(
                    'override_percentage',
                    8,
                    4
                )->nullable();

                $table->decimal(
                    'override_unit_rate',
                    18,
                    6
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Quantity
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'quantity',
                    18,
                    4
                )->default(1);

                /*
                |--------------------------------------------------------------------------
                | Billing Lifecycle
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'billing_start_date'
                );

                $table->date(
                    'billing_end_date'
                )->nullable();

                $table->date(
                    'next_billing_date'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Financial Rules
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'proration_enabled'
                )->default(false);

                $table->boolean(
                    'is_required'
                )->default(true);

                $table->boolean(
                    'is_taxable'
                )->default(false);

                $table->boolean(
                    'is_discountable'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Suspension Controls
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_suspended'
                )->default(false);

                $table->text(
                    'suspension_reason'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Processing Priority
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'priority'
                )->default(0);

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

                    'agreement_id',

                    'charge_model_id',

                    'billing_start_date',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Enterprise Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'agreement_id',
                ]);

                $table->index([

                    'company_id',

                    'charge_model_id',
                ]);

                $table->index([

                    'company_id',

                    'charge_type_id',
                ]);

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'is_suspended',
                ]);

                $table->index([

                    'company_id',

                    'next_billing_date',
                ]);

                $table->index([

                    'company_id',

                    'billing_start_date',
                ]);

                $table->index([

                    'company_id',

                    'billing_end_date',
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
            'agreement_charges'
        );
    }
};