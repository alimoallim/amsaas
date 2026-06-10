<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->foreignUuid('charge_type_id')
                ->nullable()
                ->after('monthly_invoice_id')
                ->constrained('charge_types')
                ->nullOnDelete();

            $table->index('charge_type_id', 'idx_line_items_charge_type');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropIndex('idx_line_items_charge_type');
            $table->dropConstrainedForeignId('charge_type_id');
        });
    }
};
