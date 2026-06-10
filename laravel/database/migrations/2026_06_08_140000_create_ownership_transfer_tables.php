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
            $table->string('ownership_transfer_certificate_path', 500)->nullable()->after('completion_certificate_path');
            $table->string('title_deed_number', 100)->nullable()->after('title_deed_issued');
        });

        Schema::create('apartment_ownership_history', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('apartment_id')->constrained('apartments')->cascadeOnDelete();
            $table->foreignUuid('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->foreignUuid('sale_agreement_id')->constrained('sale_agreements')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->string('title_deed_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['apartment_id', 'transfer_date'], 'idx_ownership_history_apartment');
            $table->index(['sale_agreement_id'], 'idx_ownership_history_sale');
        });

        Schema::create('sale_ownership_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('sale_agreement_id')->constrained('sale_agreements')->cascadeOnDelete();
            $table->string('step', 20);
            $table->foreignUuid('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sale_agreement_id', 'step'], 'uq_sale_ownership_approval_step');
        });

        DB::statement(
            "ALTER TABLE sale_ownership_approvals ADD CONSTRAINT chk_ownership_approval_step CHECK (step IN ('legal', 'finance', 'manager'))",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_ownership_approvals');
        Schema::dropIfExists('apartment_ownership_history');

        Schema::table('sale_agreements', function (Blueprint $table) {
            $table->dropColumn(['ownership_transfer_certificate_path', 'title_deed_number']);
        });
    }
};
