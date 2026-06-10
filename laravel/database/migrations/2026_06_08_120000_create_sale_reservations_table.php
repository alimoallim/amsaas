<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('reservation_number', 50);
            $table->foreignUuid('apartment_id')->constrained('apartments')->cascadeOnDelete();
            $table->foreignUuid('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->decimal('deposit_amount', 14, 2);
            $table->decimal('reserved_price', 14, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('expiry_date');
            $table->string('status', 30)->default('pending_deposit');
            $table->foreignUuid('deposit_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'reservation_number'], 'sale_reservations_company_number_unique');
            $table->index(['company_id', 'status'], 'idx_sale_reservations_company_status');
            $table->index(['apartment_id', 'status'], 'idx_sale_reservations_apartment_status');
            $table->index('expiry_date', 'idx_sale_reservations_expiry');
        });

        DB::statement("ALTER TABLE sale_reservations ADD CONSTRAINT chk_sale_reservation_deposit CHECK (deposit_amount >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_reservations');
    }
};
