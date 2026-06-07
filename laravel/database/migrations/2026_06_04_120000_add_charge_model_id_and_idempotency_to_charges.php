<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            if (! Schema::hasColumn('charges', 'charge_model_id')) {
                $table->uuid('charge_model_id')->nullable()->after('charge_type_id');
            }
        });

        Schema::table('charges', function (Blueprint $table) {
            $table->unique(
                ['meter_reading_id', 'charge_model_id'],
                'uq_charges_meter_reading_charge_model'
            );
        });
    }

    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropUnique('uq_charges_meter_reading_charge_model');
            $table->dropColumn('charge_model_id');
        });
    }
};
