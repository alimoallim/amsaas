<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('billing_items') || ! Schema::hasTable('monthly_invoices')) {
            return;
        }

        Schema::table('billing_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('billing_items', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('monthly_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('billing_items') || ! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('billing_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('billing_items', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });
    }
};
