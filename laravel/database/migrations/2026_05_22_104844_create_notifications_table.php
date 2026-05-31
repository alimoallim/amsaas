<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('type');
            $table->string('notifiable_type');
            $table->uuid('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Standard Indexes
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifications_notifiable');
            $table->index(['company_id', 'read_at'], 'idx_notifications_company_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
