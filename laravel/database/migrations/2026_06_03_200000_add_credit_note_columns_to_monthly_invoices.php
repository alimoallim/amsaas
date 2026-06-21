<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_invoices', function (Blueprint $table) {
            $table->string('credit_note_number', 100)->nullable()->after('voided_by');
            $table->text('credit_note_reason')->nullable()->after('credit_note_number');
            $table->timestamp('credit_noted_at')->nullable()->after('credit_note_reason');
            $table->foreignUuid('credit_noted_by')->nullable()->after('credit_noted_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_noted_by');
            $table->dropColumn(['credit_note_number', 'credit_note_reason', 'credit_noted_at']);
        });
    }
};
