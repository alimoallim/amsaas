<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_deposit_applications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('sale_agreement_id')->constrained('sale_agreements')->cascadeOnDelete();
            $table->foreignUuid('sale_reservation_id')->constrained('sale_reservations')->cascadeOnDelete();
            $table->foreignUuid('deposit_payment_id')->constrained('payments')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignUuid('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'sale_agreement_id'], 'idx_sale_deposit_apps_company_sale');
            $table->index(['sale_reservation_id'], 'idx_sale_deposit_apps_reservation');
        });

        DB::statement('ALTER TABLE sale_deposit_applications ADD CONSTRAINT chk_sale_deposit_application_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_deposit_applications');
    }
};
