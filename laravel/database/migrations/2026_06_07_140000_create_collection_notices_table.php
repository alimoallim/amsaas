<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_notices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('delinquency_flag_id')->constrained('delinquency_flags')->cascadeOnDelete();
            $table->foreignUuid('monthly_invoice_id')->constrained('monthly_invoices')->cascadeOnDelete();
            $table->string('notice_type', 30);
            $table->string('file_path', 500);
            $table->foreignUuid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['delinquency_flag_id', 'notice_type'], 'uniq_notice_per_flag_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_notices');
    }
};
