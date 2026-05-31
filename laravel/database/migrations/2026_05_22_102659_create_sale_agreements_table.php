<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'sale_agreements',

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
                | Sale Financial Terms
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'sale_price',
                    14,
                    2
                );

                $table->decimal(
                    'down_payment',
                    14,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Installment Configuration
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_installment_sale'
                )->default(false);

                $table->integer(
                    'installment_months'
                )->nullable();

                $table->decimal(
                    'monthly_installment_amount',
                    14,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Ownership Transfer
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'ownership_transfer_date'
                )->nullable();

                $table->boolean(
                    'ownership_transferred'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Commission / Brokerage
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'broker_commission',
                    14,
                    2
                )->nullable();

                $table->string(
                    'broker_name',
                    255
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Legal & Closing
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'closing_date'
                )->nullable();

                $table->boolean(
                    'title_deed_issued'
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
                    'is_installment_sale'
                );

                $table->index(
                    'ownership_transferred'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'sale_agreements'
        );
    }
};