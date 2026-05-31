<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {

            $table->uuid('id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'));

            $table->foreignUuid('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            $table->string('name', 255);

            $table->string('type', 100)
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->string('city', 100)
                ->nullable();

            $table->string('country', 100)
                ->nullable();

            $table->string('currency_code', 3)
                ->default('USD');

            $table->integer('total_units')
                ->default(0);

            $table->integer('floors')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->softDeletes();

            $table->index(
                'company_id',
                'idx_buildings_company_id'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};