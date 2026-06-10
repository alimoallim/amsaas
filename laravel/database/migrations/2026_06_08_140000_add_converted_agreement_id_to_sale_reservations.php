<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_reservations', function (Blueprint $table) {
            $table->foreignUuid('converted_agreement_id')
                ->nullable()
                ->after('status')
                ->constrained('agreements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_agreement_id');
        });
    }
};
