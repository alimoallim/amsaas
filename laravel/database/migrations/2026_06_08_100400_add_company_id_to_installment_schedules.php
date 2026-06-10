<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installment_schedules', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->after('id')
                ->constrained('companies')->cascadeOnDelete();

            $table->index('company_id', 'idx_installment_schedules_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('installment_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_installment_schedules_company_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
