<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0: align active billing line amounts to NUMERIC(14,4) per foundation schema.
 * monthly_invoices generated columns (total_amount, balance_due) remain 12,2 until a dedicated migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('billing_items')) {
            return;
        }

        $billingAmountColumns = [
            'base_amount',
            'tax_amount',
            'discount_amount',
            'penalty_amount',
            'adjustment_amount',
            'subtotal_amount',
            'total_amount',
        ];

        foreach ($billingAmountColumns as $column) {
            if (Schema::hasColumn('billing_items', $column)) {
                DB::statement("ALTER TABLE billing_items ALTER COLUMN {$column} TYPE NUMERIC(14,4)");
            }
        }

        if (Schema::hasTable('invoice_line_items') && Schema::hasColumn('invoice_line_items', 'amount')) {
            DB::statement('ALTER TABLE invoice_line_items ALTER COLUMN amount TYPE NUMERIC(14,4)');
        }

        if (Schema::hasTable('invoice_line_items') && Schema::hasColumn('invoice_line_items', 'unit_price')) {
            DB::statement('ALTER TABLE invoice_line_items ALTER COLUMN unit_price TYPE NUMERIC(14,4)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('billing_items')) {
            return;
        }

        $billingAmountColumns = [
            'base_amount',
            'tax_amount',
            'discount_amount',
            'penalty_amount',
            'adjustment_amount',
            'subtotal_amount',
            'total_amount',
        ];

        foreach ($billingAmountColumns as $column) {
            if (Schema::hasColumn('billing_items', $column)) {
                DB::statement("ALTER TABLE billing_items ALTER COLUMN {$column} TYPE NUMERIC(18,2)");
            }
        }

        if (Schema::hasTable('invoice_line_items') && Schema::hasColumn('invoice_line_items', 'amount')) {
            DB::statement('ALTER TABLE invoice_line_items ALTER COLUMN amount TYPE NUMERIC(12,2)');
        }

        if (Schema::hasTable('invoice_line_items') && Schema::hasColumn('invoice_line_items', 'unit_price')) {
            DB::statement('ALTER TABLE invoice_line_items ALTER COLUMN unit_price TYPE NUMERIC(12,4)');
        }
    }
};
