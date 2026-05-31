<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('apartment_id')->constrained('apartments')->onDelete('cascade');
            $table->string('invoice_number', 100)->unique();
            $table->string('contract_type', 20); // rental | sale
            $table->uuid('contract_id'); // Polymorphic foreign key
            $table->integer('billing_year');
            $table->integer('billing_month');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal_rent', 12, 2)->default(0);
            $table->decimal('subtotal_utilities', 12, 2)->default(0);
            $table->decimal('subtotal_services', 12, 2)->default(0);
            $table->decimal('subtotal_installment', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('draft'); // draft | finalized | partially_paid | paid | overdue | cancelled
            $table->text('notes')->nullable();
            $table->foreignUuid('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes and Unique Constraints
            $table->unique(['apartment_id', 'billing_year', 'billing_month', 'contract_type'], 'uq_invoice_period');
            $table->index(['company_id', 'billing_year', 'billing_month'], 'idx_invoices_company_period');
            $table->index(['company_id', 'status'], 'idx_invoices_company_status');
            $table->index(['contract_type', 'contract_id'], 'idx_invoices_contract');
            $table->index(['due_date', 'status'], 'idx_invoices_due_status');
        });

        // Computed Columns and Check Constraints
        DB::statement('ALTER TABLE monthly_invoices ADD COLUMN total_amount NUMERIC(12,2) GENERATED ALWAYS AS (subtotal_rent + subtotal_utilities + subtotal_services + subtotal_installment - discount_amount) STORED');
        DB::statement('ALTER TABLE monthly_invoices ADD COLUMN balance_due NUMERIC(12,2) GENERATED ALWAYS AS (subtotal_rent + subtotal_utilities + subtotal_services + subtotal_installment - discount_amount - paid_amount) STORED');
        
        DB::statement('ALTER TABLE monthly_invoices ADD CONSTRAINT chk_invoice_contract_type CHECK (contract_type IN (\'rental\', \'sale\'))');
        DB::statement('ALTER TABLE monthly_invoices ADD CONSTRAINT chk_invoice_billing_month CHECK (billing_month BETWEEN 1 AND 12)');
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_invoices');
    }
};
