<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_period_closes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('fiscal_month');
            $table->boolean('trial_balance_balanced')->default(false);
            $table->decimal('total_debits', 14, 4)->default(0);
            $table->decimal('total_credits', 14, 4)->default(0);
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year', 'fiscal_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_period_closes');
    }
};
