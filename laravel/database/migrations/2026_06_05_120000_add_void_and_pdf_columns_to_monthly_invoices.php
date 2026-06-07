<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_invoices', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('notes');
            $table->string('dispatch_status', 30)->nullable()->after('file_path');
            $table->text('void_reason')->nullable()->after('dispatch_status');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
            $table->foreignUuid('voided_by')->nullable()->after('voided_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['file_path', 'dispatch_status', 'void_reason', 'voided_at']);
        });
    }
};
