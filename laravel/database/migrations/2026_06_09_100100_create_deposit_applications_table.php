<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_applications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('agreement_id')->constrained('agreements')->cascadeOnDelete();
            $table->foreignUuid('monthly_invoice_id')->constrained('monthly_invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignUuid('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'agreement_id'], 'idx_deposit_apps_company_agreement');
            $table->index(['monthly_invoice_id'], 'idx_deposit_apps_invoice');
        });

        DB::statement('ALTER TABLE deposit_applications ADD CONSTRAINT chk_deposit_application_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_applications');
    }
};
