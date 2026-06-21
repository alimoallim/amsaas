<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('amount');
            $table->text('refund_reason')->nullable()->after('notes');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
            $table->foreignUuid('refunded_by')->nullable()->after('refunded_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn(['refunded_amount', 'refund_reason', 'refunded_at']);
        });
    }
};
