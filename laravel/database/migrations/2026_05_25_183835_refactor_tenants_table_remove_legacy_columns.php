<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            $table->dropColumn([

                'full_name',
                'emergency_contact',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            $table->string('full_name')
                ->nullable(false);

            $table->text('emergency_contact')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);
        });
    }
};