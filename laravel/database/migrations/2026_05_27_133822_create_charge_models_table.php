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

            'charge_models',

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
                | Charge Type
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'charge_type_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Identification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'code',
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
                | Financial Configuration
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'currency',
                    10
                )->default('USD');

                $table->decimal(
                    'base_amount',
                    18,
                    2
                )->nullable();

                $table->decimal(
                    'minimum_amount',
                    18,
                    2
                )->nullable();

                $table->decimal(
                    'maximum_amount',
                    18,
                    2
                )->nullable();

                $table->decimal(
                    'unit_rate',
                    18,
                    6
                )->nullable();

                $table->decimal(
                    'percentage_rate',
                    8,
                    4
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Billing Engine
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'billing_frequency',
                    50
                );

                $table->string(
                    'pricing_strategy',
                    50
                );

                $table->string(
                    'meter_type',
                    50
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Advanced Billing Rules
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'tier_configuration'
                )->nullable();

                $table->longText(
                    'formula_expression'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Financial Controls
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'proration_enabled'
                )->default(false);

                $table->unsignedInteger(
                    'grace_period_days'
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Late Fee Engine
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'late_fee_enabled'
                )->default(false);

                $table->string(
                    'late_fee_type',
                    50
                )->nullable();

                $table->decimal(
                    'late_fee_value',
                    18,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Tax Engine
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'taxable'
                )->default(false);

                $table->decimal(
                    'tax_rate',
                    8,
                    4
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Effective Dating
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'effective_from'
                );

                $table->date(
                    'effective_to'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Automation
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'auto_generate'
                )->default(true);

                $table->boolean(
                    'requires_approval'
                )->default(false);

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
                | Sorting
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'sort_order'
                )->default(0);

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

                    'company_id',

                    'code',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Performance Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'charge_type_id',
                ]);

                $table->index([

                    'company_id',

                    'pricing_strategy',
                ]);

                $table->index([

                    'company_id',

                    'billing_frequency',
                ]);

                $table->index([

                    'company_id',

                    'effective_from',
                ]);

                $table->index([

                    'company_id',

                    'effective_to',
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
            'charge_models'
        );
    }
};