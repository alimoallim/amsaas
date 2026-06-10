<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installment_schedules', function (Blueprint $table) {
            $table->foreignUuid('monthly_invoice_id')
                ->nullable()
                ->after('sale_agreement_id')
                ->constrained('monthly_invoices')
                ->nullOnDelete();

            $table->index('monthly_invoice_id', 'idx_installment_schedule_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('installment_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_installment_schedule_invoice');
            $table->dropConstrainedForeignId('monthly_invoice_id');
        });
    }
};
