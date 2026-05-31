<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignUuid('monthly_invoice_id')->constrained('monthly_invoices')->onDelete('cascade');
            $table->decimal('amount_allocated', 12, 2);
            $table->timestamps();

            // Standard Indexes
            $table->index('payment_id', 'idx_allocations_payment');
            $table->index('monthly_invoice_id', 'idx_allocations_invoice');
            
            // Prevent double-allocating the exact same payment to the same invoice (updates should modify the existing row)
            $table->unique(['payment_id', 'monthly_invoice_id'], 'uq_payment_invoice_allocation');
        });

        // Specialized Check Constraints
        DB::statement('ALTER TABLE payment_allocations ADD CONSTRAINT chk_allocation_amount CHECK (amount_allocated > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
