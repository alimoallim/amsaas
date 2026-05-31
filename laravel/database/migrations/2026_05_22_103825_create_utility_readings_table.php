<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_readings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('apartment_id')->constrained('apartments')->onDelete('cascade');
            $table->foreignUuid('utility_rate_config_id')->constrained('utility_rate_configs');
            $table->string('utility_type', 30);
            $table->integer('billing_year');
            $table->integer('billing_month');
            $table->decimal('reading_start', 12, 3)->nullable();
            $table->decimal('reading_end', 12, 3)->nullable();
            $table->decimal('units_consumed', 12, 3);
            $table->decimal('rate_per_unit', 10, 4);
            $table->string('status', 20)->default('draft'); // draft | confirmed | invoiced
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Indexes and Unique Constraints
            $table->unique(['apartment_id', 'utility_type', 'billing_year', 'billing_month'], 'uq_utility_reading_period');
            $table->index(['billing_year', 'billing_month', 'utility_type'], 'idx_utility_readings_period');
            $table->index(['apartment_id', 'billing_year', 'billing_month'], 'idx_utility_readings_apartment');
        });

        // Computed Columns and Check Constraints
        DB::statement('ALTER TABLE utility_readings ADD COLUMN total_charge NUMERIC(12,2) GENERATED ALWAYS AS (units_consumed * rate_per_unit) STORED');
        DB::statement('ALTER TABLE utility_readings ADD CONSTRAINT chk_reading_billing_month CHECK (billing_month BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE utility_readings ADD CONSTRAINT chk_reading_units_consumed CHECK (units_consumed >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_readings');
    }
};