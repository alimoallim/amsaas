<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_purpose', 40)->default('rent')->after('status');
            $table->foreignUuid('agreement_id')->nullable()->after('buyer_id')->constrained('agreements')->nullOnDelete();

            $table->index(['company_id', 'agreement_id', 'payment_purpose'], 'idx_payments_agreement_purpose');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_agreement_purpose');
            $table->dropConstrainedForeignId('agreement_id');
            $table->dropColumn('payment_purpose');
        });
    }
};
