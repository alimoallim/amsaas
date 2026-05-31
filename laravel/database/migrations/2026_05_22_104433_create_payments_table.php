<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->foreignUuid('buyer_id')->nullable()->constrained('buyers')->onDelete('set null');
            $table->string('receipt_number', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50); // cash | bank_transfer | mobile_money | cheque
            $table->string('reference_number', 100)->nullable();
            $table->string('status', 30)->default('completed'); // pending | completed | bounced | refunded
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Standard Indexes
            $table->index(['company_id', 'payment_date'], 'idx_payments_company_date');
            $table->index(['tenant_id', 'status'], 'idx_payments_tenant');
            $table->index(['buyer_id', 'status'], 'idx_payments_buyer');
        });

        // Specialized Check Constraints
        DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payment_amount CHECK (amount > 0)');
        // Ensure a payment belongs to either a tenant or a buyer, but not both or neither
        DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payment_owner CHECK ((tenant_id IS NOT NULL AND buyer_id IS NULL) OR (buyer_id IS NOT NULL AND tenant_id IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
