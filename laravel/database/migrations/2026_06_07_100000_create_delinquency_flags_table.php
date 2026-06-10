<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delinquency_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('monthly_invoice_id')->constrained('monthly_invoices')->cascadeOnDelete();
            $table->date('first_overdue_date');
            $table->string('escalation_stage', 30)->default('first_notice');
            $table->timestamp('stage_updated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique('monthly_invoice_id');
            $table->index(['company_id', 'escalation_stage', 'resolved_at'], 'idx_delinquency_company_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delinquency_flags');
    }
};
