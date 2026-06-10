<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignUuid('buyer_id')->nullable()->after('tenant_id')
                ->constrained('buyers')->nullOnDelete();

            $table->index('buyer_id', 'idx_agreements_buyer_id');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropIndex('idx_agreements_buyer_id');
            $table->dropConstrainedForeignId('buyer_id');
        });
    }
};
