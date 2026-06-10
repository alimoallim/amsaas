<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_agreements', function (Blueprint $table) {
            $table->decimal('financed_amount', 14, 2)->nullable()->after('down_payment');
            $table->unsignedSmallInteger('plan_duration_years')->nullable()->after('monthly_installment_amount');
            $table->unsignedSmallInteger('plan_duration_months')->nullable()->after('plan_duration_years');
        });

        DB::table('sale_agreements')
            ->where('is_installment_sale', true)
            ->update([
                'financed_amount' => DB::raw('GREATEST(sale_price - COALESCE(down_payment, 0), 0)'),
            ]);

        DB::table('sale_agreements')
            ->where('is_installment_sale', true)
            ->whereNotNull('installment_months')
            ->whereNull('plan_duration_months')
            ->whereNull('plan_duration_years')
            ->update([
                'plan_duration_months' => DB::raw('installment_months'),
            ]);
    }

    public function down(): void
    {
        Schema::table('sale_agreements', function (Blueprint $table) {
            $table->dropColumn(['financed_amount', 'plan_duration_years', 'plan_duration_months']);
        });
    }
};
