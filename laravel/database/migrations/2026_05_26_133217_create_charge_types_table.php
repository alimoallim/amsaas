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

            'charge_types',

            function (
                Blueprint $table
            ) {

                /*
                |--------------------------------------------------------------------------
                | Primary
                |--------------------------------------------------------------------------
                */

                $table->uuid('id')
                    ->primary();

                /*
                |--------------------------------------------------------------------------
                | Tenant Isolation
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
                    'code',
                    100
                );

                $table->string(
                    'name',
                    255
                );

                $table->string(
                    'short_name',
                    100
                )->nullable();

                $table->text(
                    'description'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Financial Classification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'category',
                    50
                );

                $table->string(
                    'billing_behavior',
                    50
                );

                $table->string(
                    'calculation_method',
                    50
                );

                $table->string(
                    'billing_frequency',
                    50
                );

                $table->string(
                    'financial_classification',
                    50
                );

                /*
                |--------------------------------------------------------------------------
                | Default Financial Settings
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'default_currency',
                    10
                )->default('USD');

                $table->decimal(
                    'default_amount',
                    18,
                    2
                )->nullable();

                $table->decimal(
                    'default_percentage',
                    8,
                    4
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Financial Rules
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_recurring'
                )->default(false);

                $table->boolean(
                    'is_metered'
                )->default(false);

                $table->boolean(
                    'requires_meter_reading'
                )->default(false);

                $table->boolean(
                    'is_taxable'
                )->default(false);

                $table->boolean(
                    'is_refundable'
                )->default(false);

                $table->boolean(
                    'allow_manual_override'
                )->default(true);

                $table->boolean(
                    'allow_proration'
                )->default(false);

                $table->boolean(
                    'allow_discount'
                )->default(false);

                $table->boolean(
                    'allow_penalty'
                )->default(false);

                $table->boolean(
                    'allow_adjustment'
                )->default(true);

                $table->boolean(
                    'auto_generate'
                )->default(false);

                $table->boolean(
                    'affects_occupancy'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Accounting Integration
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'ledger_account_code',
                    100
                )->nullable();

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
                | Status
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status',
                    30
                )->default('active');

                /*
                |--------------------------------------------------------------------------
                | Audit
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
                | Constraints
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'company_id'
                )
                ->references('id')
                ->on('companies')
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
                | Enterprise Unique Constraints
                |--------------------------------------------------------------------------
                */

                $table->unique([

                    'company_id',

                    'code',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Enterprise Performance Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([

                    'company_id',

                    'category',
                ]);

                $table->index([

                    'company_id',

                    'status',
                ]);

                $table->index([

                    'company_id',

                    'billing_behavior',
                ]);

                $table->index([

                    'company_id',

                    'billing_frequency',
                ]);

                $table->index([

                    'company_id',

                    'is_metered',
                ]);

                $table->index([

                    'company_id',

                    'is_recurring',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists(
            'charge_types'
        );
    }
};