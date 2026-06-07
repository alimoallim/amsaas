<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */

            $table->uuid('id')
                ->primary();

            /*
            |--------------------------------------------------------------------------
            | Multi-Tenant Ownership
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('company_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Property Context
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

            /*
            |--------------------------------------------------------------------------
            | Customer Context
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUuid('buyer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Agreement Context
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('rental_agreement_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUuid('sale_agreement_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Invoice Identity
            |--------------------------------------------------------------------------
            */

  

/*
            |--------------------------------------------------------------------------
            | Invoice Identity
            |--------------------------------------------------------------------------
            */

            // 1. Define the column first
            $table->string('invoice_number', 50); 

            // 2. Then define the index
            $table->unique(['company_id', 'invoice_number'], 'uq_invoice_per_company');

            $table->string(
                'reference_number',
                100
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Invoice Classification
            |--------------------------------------------------------------------------
            */

            $table->string(
                'invoice_type',
                50
            );

            /*
            |--------------------------------------------------------------------------
            | Examples:
            | rental
            | utility
            | service_fee
            | installment
            | penalty
            | mixed
            |--------------------------------------------------------------------------
            */

            $table->string(
                'status',
                30
            )->default('draft');

            /*
            |--------------------------------------------------------------------------
            | draft
            | issued
            | partially_paid
            | paid
            | overdue
            | cancelled
            | written_off
            |--------------------------------------------------------------------------
            */

            /*
            |--------------------------------------------------------------------------
            | Billing Period
            |--------------------------------------------------------------------------
            */

            $table->date(
                'billing_period_start'
            )->nullable();

            $table->date(
                'billing_period_end'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            $table->date(
                'issue_date'
            );

            $table->date(
                'due_date'
            );

            $table->date(
                'paid_date'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            $table->string(
                'currency',
                10
            )->default('USD');

            $table->decimal(
                'exchange_rate',
                18,
                6
            )->default(1);

            /*
            |--------------------------------------------------------------------------
            | Financial Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'subtotal',
                18,
                2
            )->default(0);

            $table->decimal(
                'discount_amount',
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
                'total_amount',
                18,
                2
            )->default(0);

            $table->decimal(
                'paid_amount',
                18,
                2
            )->default(0);

            $table->decimal(
                'balance_due',
                18,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Operational Flags
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'is_system_generated'
            )->default(false);

            $table->boolean(
                'is_recurring'
            )->default(false);

            $table->boolean(
                'is_locked'
            )->default(false);

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text(
                'description'
            )->nullable();

            $table->text(
                'notes'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid(
                'created_by'
            )
                ->nullable()
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreignUuid(
                'updated_by'
            )
                ->nullable()
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'company_id',
                'status',
            ]);

            $table->index([
                'company_id',
                'invoice_type',
            ]);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'buyer_id',
                'status',
            ]);

            $table->index([
                'issue_date',
                'due_date',
            ]);

            $table->index([
                'building_id',
                'apartment_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'invoices'
        );
    }
};