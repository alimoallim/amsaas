<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_agreements', function (Blueprint $table) {
            $table->string('completion_certificate_path', 500)->nullable()->after('special_terms');
        });

        Schema::create('sale_payment_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignUuid('sale_agreement_id')->constrained('sale_agreements')->cascadeOnDelete();
            $table->decimal('amount_allocated', 14, 2);
            $table->timestamps();

            $table->index('payment_id', 'idx_sale_alloc_payment');
            $table->index('sale_agreement_id', 'idx_sale_alloc_agreement');
            $table->unique(['payment_id', 'sale_agreement_id'], 'uq_sale_payment_allocation');
        });

        DB::statement(
            'ALTER TABLE sale_payment_allocations ADD CONSTRAINT chk_sale_allocation_amount CHECK (amount_allocated > 0)',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payment_allocations');

        Schema::table('sale_agreements', function (Blueprint $table) {
            $table->dropColumn('completion_certificate_path');
        });
    }
};
