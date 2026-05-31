<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_rate_configs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('building_id')->constrained('buildings')->onDelete('cascade');
            $table->string('utility_type', 30); // electricity | water
            $table->decimal('rate_per_unit', 10, 4);
            $table->string('unit_label', 20);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Standard Index
            $table->index(['building_id', 'utility_type', 'effective_from'], 'idx_utility_rate_building');
        });

        // Specialized Check Constraints
        DB::statement('ALTER TABLE utility_rate_configs ADD CONSTRAINT chk_utility_rate_type CHECK (utility_type IN (\'electricity\', \'water\'))');
        DB::statement('ALTER TABLE utility_rate_configs ADD CONSTRAINT chk_utility_rate_amount CHECK (rate_per_unit > 0)');

        // High-Integrity Guard: One active rate per building per utility type
        DB::statement('CREATE UNIQUE INDEX uq_utility_rate_active ON utility_rate_configs (building_id, utility_type) WHERE is_active = TRUE');
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_rate_configs');
    }
};
