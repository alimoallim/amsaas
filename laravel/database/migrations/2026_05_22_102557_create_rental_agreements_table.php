<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'rental_agreements',

            function (
                Blueprint $table
            ) {

                /*
                |--------------------------------------------------------------------------
                | Primary Key
                |--------------------------------------------------------------------------
                */

                $table->uuid('id')
                    ->primary();

                /*
                |--------------------------------------------------------------------------
                | Shared Agreement Reference
                |--------------------------------------------------------------------------
                */

                $table->foreign('id')

                    ->references('id')

                    ->on('agreements')

                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Rental Financial Terms
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'monthly_rent',
                    14,
                    2
                );

                $table->decimal(
                    'security_deposit',
                    14,
                    2
                )->nullable();

                $table->decimal(
                    'advance_rent_amount',
                    14,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Billing Configuration
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'billing_cycle',
                    20
                )->default('monthly');

                $table->integer(
                    'payment_due_day'
                )->default(1);

                $table->integer(
                    'grace_period_days'
                )->default(5);

                /*
                |--------------------------------------------------------------------------
                | Late Fee Policy
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'late_fee_enabled'
                )->default(false);

                $table->decimal(
                    'late_fee_amount',
                    12,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Utility Responsibility
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'includes_water'
                )->default(false);

                $table->boolean(
                    'includes_electricity'
                )->default(false);

                $table->boolean(
                    'includes_internet'
                )->default(false);

                $table->boolean(
                    'includes_garbage'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Renewal Management
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'auto_renew'
                )->default(false);

                $table->integer(
                    'renewal_notice_days'
                )->default(30);

                /*
                |--------------------------------------------------------------------------
                | Occupancy Rules
                |--------------------------------------------------------------------------
                */

                $table->integer(
                    'max_occupants'
                )->nullable();

                $table->boolean(
                    'pets_allowed'
                )->default(false);

                $table->boolean(
                    'smoking_allowed'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Operational Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'special_terms'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'billing_cycle'
                );

                $table->index(
                    'auto_renew'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'rental_agreements'
        );
    }
};