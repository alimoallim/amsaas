<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0: align monthly_invoices money columns to NUMERIC(14,4) including generated totals.
 */
return new class extends Migration
{
    private const SUBTOTAL_COLUMNS = [
        'subtotal_rent',
        'subtotal_utilities',
        'subtotal_services',
        'subtotal_installment',
        'discount_amount',
        'paid_amount',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('monthly_invoices')) {
            return;
        }

        DB::statement('ALTER TABLE monthly_invoices DROP COLUMN IF EXISTS total_amount');
        DB::statement('ALTER TABLE monthly_invoices DROP COLUMN IF EXISTS balance_due');

        foreach (self::SUBTOTAL_COLUMNS as $column) {
            if (Schema::hasColumn('monthly_invoices', $column)) {
                DB::statement("ALTER TABLE monthly_invoices ALTER COLUMN {$column} TYPE NUMERIC(14,4)");
            }
        }

        DB::statement(
            'ALTER TABLE monthly_invoices ADD COLUMN total_amount NUMERIC(14,4) GENERATED ALWAYS AS (
                subtotal_rent + subtotal_utilities + subtotal_services + subtotal_installment - discount_amount
            ) STORED'
        );

        DB::statement(
            'ALTER TABLE monthly_invoices ADD COLUMN balance_due NUMERIC(14,4) GENERATED ALWAYS AS (
                subtotal_rent + subtotal_utilities + subtotal_services + subtotal_installment - discount_amount - paid_amount
            ) STORED'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('monthly_invoices')) {
            return;
        }

        DB::statement('ALTER TABLE monthly_invoices DROP COLUMN IF EXISTS total_amount');
        DB::statement('ALTER TABLE monthly_invoices DROP COLUMN IF EXISTS balance_due');

        foreach (self::SUBTOTAL_COLUMNS as $column) {
            if (Schema::hasColumn('monthly_invoices', $column)) {
                DB::statement("ALTER TABLE monthly_invoices ALTER COLUMN {$column} TYPE NUMERIC(12,2)");
            }
        }

        DB::statement(
            'ALTER TABLE monthly_invoices ADD COLUMN total_amount NUMERIC(12,2) GENERATED ALWAYS AS (
                subtotal_rent + subtotal_utilities + subtotal_services + subtotal_installment - discount_amount
            ) STORED'
        );

        DB::statement(
            'ALTER TABLE monthly_invoices ADD COLUMN balance_due NUMERIC(12,2) GENERATED ALWAYS AS (
                subtotal_rent + subtotal_utilities + subtotal_services + subtotal_installment - discount_amount - paid_amount
            ) STORED'
        );
    }
};
