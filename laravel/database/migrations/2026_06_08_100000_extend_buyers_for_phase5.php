<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->string('buyer_code', 50)->nullable()->after('company_id');
            $table->foreignUuid('tenant_id')->nullable()->after('buyer_code')
                ->constrained('tenants')->nullOnDelete();
            $table->string('country', 100)->nullable()->after('date_of_birth');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('postal_code', 20)->nullable()->after('address');

            $table->unique(['company_id', 'buyer_code'], 'buyers_company_code_unique');
            $table->index('tenant_id', 'idx_buyers_tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropUnique('buyers_company_code_unique');
            $table->dropIndex('idx_buyers_tenant_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['buyer_code', 'country', 'city', 'postal_code']);
        });
    }
};
