<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_reminder_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignUuid('monthly_invoice_id')->constrained('monthly_invoices')->cascadeOnDelete();
            $table->foreignUuid('delinquency_flag_id')->nullable()->constrained('delinquency_flags')->nullOnDelete();
            $table->string('reminder_type', 40);
            $table->string('channel', 20)->default('email');
            $table->string('status', 20)->default('queued');
            $table->string('recipient', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignUuid('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['monthly_invoice_id', 'reminder_type'], 'uniq_reminder_per_invoice_type');
            $table->index(['company_id', 'tenant_id', 'created_at'], 'idx_reminder_logs_tenant');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('reminder_opt_out')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('reminder_opt_out');
        });

        Schema::dropIfExists('collection_reminder_logs');
    }
};
