<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Rename Legacy ERP Fields
            |--------------------------------------------------------------------------
            */

            $table->renameColumn(
                'currency_code',
                'operating_currency'
            );

            $table->renameColumn(
                'floors',
                'total_floors'
            );

            /*
            |--------------------------------------------------------------------------
            | New ERP Fields
            |--------------------------------------------------------------------------
            */

            $table->string(
                'code',
                100
            )->nullable();

            $table->string(
                'timezone',
                100
            )->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {

            $table->renameColumn(
                'operating_currency',
                'currency_code'
            );

            $table->renameColumn(
                'total_floors',
                'floors'
            );

            $table->dropColumn([
                'code',
                'timezone'
            ]);
        });
    }
};