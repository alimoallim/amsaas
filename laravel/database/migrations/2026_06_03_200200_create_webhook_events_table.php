<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 50);
            $table->string('external_id', 150);
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('status', 30)->default('processed');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index(['company_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
