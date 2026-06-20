<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_reminder_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->json('days_before_due')->default('[]');
            $table->json('days_after_due')->default('[1,7,14,30]');
            $table->json('channels')->default('["email"]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_reminder_schedules');
    }
};
