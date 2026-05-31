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

        Schema::create('charges', function (
            Blueprint $table
        ) {

            /*
            |--------------------------------------------------------------------------
            | Primary UUID Key
            |--------------------------------------------------------------------------
            */

            $table->uuid('id');

            $table->primary('id');

            /*
            |--------------------------------------------------------------------------
            | Public UUID
            |--------------------------------------------------------------------------
            */

            $table->uuid('uuid')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Charge Identity
            |--------------------------------------------------------------------------
            */

            $table->string(
                'charge_number'
            )->unique();

            $table->string(
                'reference_number'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Multi-Tenant Ownership Hierarchy
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid(
                'company_id'
            )
            ->constrained()
            ->cascadeOnDelete();

            $table->foreignUuid(
                'building_id'
            )
            ->nullable()
            ->constrained()
            ->nullOnDelete();

            $table->foreignUuid(
                'apartment_id'
            )
            ->nullable()
            ->constrained()
            ->nullOnDelete();

            $table->foreignUuid(
                'tenant_id'
            )
            ->nullable()
            ->constrained()
            ->nullOnDelete();

            $table->foreignUuid(
                'rental_agreement_id'
            )
            ->nullable()
            ->constrained()
            ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Financial Relationships
            |--------------------------------------------------------------------------
            */

            $table->uuid(
    'billing_cycle_id'
)->nullable();

            $table->foreignUuid(
                'charge_type_id'
            )
            ->nullable()
            ->constrained()
            ->nullOnDelete();

            $table->uuid(
    'invoice_id'
)->nullable();

            $table->foreignUuid(
                'meter_reading_id'
            )
            ->nullable()
            ->constrained()
            ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Reversal / Adjustment Relationship
            |--------------------------------------------------------------------------
            */

            $table->uuid(
                'parent_charge_id'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Classification
            |--------------------------------------------------------------------------
            */

            $table->enum(
                'category',
                [
                    'rent',
                    'utility',
                    'service_fee',
                    'maintenance',
                    'penalty',
                    'tax',
                    'deposit',
                    'custom',
                ]
            )->index();

            $table->enum(
                'billing_strategy',
                [
                    'fixed',
                    'metered',
                    'adhoc',
                    'recurring',
                    'percentage',
                ]
            )->index();

            /*
            |--------------------------------------------------------------------------
            | Financial Lifecycle Status
            |--------------------------------------------------------------------------
            */

            $table->enum(
                'status',
                [
                    'draft',
                    'pending',
                    'approved',
                    'invoiced',
                    'partially_paid',
                    'paid',
                    'cancelled',
                    'reversed',
                    'overdue',
                ]
            )
            ->default('draft')
            ->index();

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
            | Descriptions
            |--------------------------------------------------------------------------
            */

            $table->text(
                'description'
            )->nullable();

            $table->longText(
                'notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial Values
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
                4
            )->default(0);

            $table->decimal(
                'subtotal_amount',
                18,
                4
            )->default(0);

            $table->decimal(
                'tax_amount',
                18,
                4
            )->default(0);

            $table->decimal(
                'discount_amount',
                18,
                4
            )->default(0);

            $table->decimal(
                'total_amount',
                18,
                4
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Meter Reading Snapshot
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'meter_previous_reading',
                18,
                4
            )->nullable();

            $table->decimal(
                'meter_current_reading',
                18,
                4
            )->nullable();

            $table->decimal(
                'meter_consumption',
                18,
                4
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Historical Snapshots
            |--------------------------------------------------------------------------
            */

            $table->string(
                'company_name_snapshot'
            )->nullable();

            $table->string(
                'building_name_snapshot'
            )->nullable();

            $table->string(
                'apartment_label_snapshot'
            )->nullable();

            $table->string(
                'tenant_name_snapshot'
            )->nullable();

            $table->string(
                'agreement_number_snapshot'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Billing Period
            |--------------------------------------------------------------------------
            */

            $table->date(
                'service_period_start'
            )->nullable();

            $table->date(
                'service_period_end'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Lifecycle Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamp(
                'charged_at'
            )->nullable();

            $table->timestamp(
                'approved_at'
            )->nullable();

            $table->timestamp(
                'invoiced_at'
            )->nullable();

            $table->timestamp(
                'paid_at'
            )->nullable();

            $table->timestamp(
                'reversed_at'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid(
                'generated_by'
            )
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $table->foreignUuid(
                'approved_by'
            )
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $table->foreignUuid(
                'reversed_by'
            )
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

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
                'tenant_id',
                'status',
            ]);

            $table->index([
                'invoice_id',
                'status',
            ]);

            $table->index([
                'billing_cycle_id',
                'status',
            ]);

            $table->index([
                'service_period_start',
                'service_period_end',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Soft Deletes + Timestamps
            |--------------------------------------------------------------------------
            */

            $table->softDeletes();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Self-Referencing FK
        |--------------------------------------------------------------------------
        */

        Schema::table('charges', function (
            Blueprint $table
        ) {

            $table->foreign(
                'parent_charge_id'
            )
            ->references('id')
            ->on('charges')
            ->nullOnDelete();

        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists(
            'charges'
        );

    }
};