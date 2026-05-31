<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_fee_configs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('building_id')->constrained('buildings')->onDelete('cascade');
            $table->foreignUuid('apartment_id')->nullable()->constrained('apartments')->onDelete('set null');
            $table->string('fee_type', 50); // security | cleaning | other
            $table->string('name', 255);
            $table->decimal('amount', 12, 2);
            $table->string('billing_scope', 30)->default('per_apartment'); // per_apartment | per_building
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Standard Indexes
            $table->index(['building_id', 'fee_type', 'is_active'], 'idx_service_fee_building');
            $table->index('company_id', 'idx_service_fee_company');
        });

        // Specialized Check Constraint
        DB::statement('ALTER TABLE service_fee_configs ADD CONSTRAINT chk_service_fee_amount CHECK (amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('service_fee_configs');
    }
};
