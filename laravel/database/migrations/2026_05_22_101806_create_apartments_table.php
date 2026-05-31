<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'apartments',

            function (
                Blueprint $table
            ) {

                /*
                |--------------------------------------------------------------------------
                | Primary
                |--------------------------------------------------------------------------
                */

                $table->uuid('id')
                    ->primary()
                    ->default(
                        DB::raw(
                            'gen_random_uuid()'
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Company
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid(
                    'company_id'
                )
                    ->constrained('companies')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Building
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid(
                    'building_id'
                )
                    ->constrained('buildings')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Unit Details
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'unit_number',
                    50
                );

                $table->integer(
                    'floor'
                )->nullable();

                $table->integer(
                    'bedrooms'
                )->default(1);

                $table->integer(
                    'bathrooms'
                )->default(1);

                $table->decimal(
                    'area_sqm',
                    10,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Property Classification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'property_type',
                    50
                )
                ->default(
                    'apartment'
                );

                /*
                |--------------------------------------------------------------------------
                | Listing Strategy
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'listing_type',
                    30
                )
                ->default(
                    'rental'
                );

                /*
                |--------------------------------------------------------------------------
                | Inventory Lifecycle
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'inventory_status',
                    30
                )
                ->default(
                    'available'
                );

                /*
                |--------------------------------------------------------------------------
                | Commercial
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'market_rent_price',
                    14,
                    2
                )->nullable();

                $table->decimal(
                    'market_sale_price',
                    14,
                    2
                )->nullable();

                $table->decimal(
                    'security_deposit',
                    14,
                    2
                )->nullable();

                $table->string(
                    'currency',
                    10
                )
                ->default('USD');

                /*
                |--------------------------------------------------------------------------
                | Features
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'has_balcony'
                )->default(false);

                $table->boolean(
                    'has_parking'
                )->default(false);

                $table->boolean(
                    'has_storage'
                )->default(false);

                $table->boolean(
                    'is_furnished'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid(
                    'created_by'
                )
                ->nullable();

                $table->foreignUuid(
                    'updated_by'
                )
                ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Constraints
                |--------------------------------------------------------------------------
                */

                $table->unique(

                    [
                        'company_id',
                        'building_id',
                        'unit_number',
                    ],

                    'uq_apartment_unit_per_building'
                );

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(

                    [
                        'company_id',
                        'listing_type',
                    ],

                    'idx_apartment_listing_type'
                );

                $table->index(

                    [
                        'company_id',
                        'inventory_status',
                    ],

                    'idx_apartment_inventory_status'
                );

                $table->index(

                    [
                        'company_id',
                        'building_id',
                    ],

                    'idx_apartment_building'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Database Check Constraints
        |--------------------------------------------------------------------------
        */

        DB::statement('
            ALTER TABLE apartments

            ADD CONSTRAINT chk_apartment_bedrooms
            CHECK (
                bedrooms BETWEEN 0 AND 20
            )
        ');

        DB::statement('
            ALTER TABLE apartments

            ADD CONSTRAINT chk_apartment_bathrooms
            CHECK (
                bathrooms BETWEEN 0 AND 20
            )
        ');

        DB::statement('
            ALTER TABLE apartments

            ADD CONSTRAINT chk_apartment_area
            CHECK (
                area_sqm IS NULL
                OR area_sqm > 0
            )
        ');

        DB::statement('
            ALTER TABLE apartments

            ADD CONSTRAINT chk_apartment_rent_price
            CHECK (
                market_rent_price IS NULL
                OR market_rent_price >= 0
            )
        ');

        DB::statement('
            ALTER TABLE apartments

            ADD CONSTRAINT chk_apartment_sale_price
            CHECK (
                market_sale_price IS NULL
                OR market_sale_price >= 0
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'apartments'
        );
    }
};