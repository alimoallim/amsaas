<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {

            $table->string('code', 3)->primary();

            $table->string('name', 50);

            $table->string('symbol', 10);

            $table->unsignedSmallInteger('precision')
                ->default(2);

            $table->timestamp('created_at')
                ->useCurrent();
        });

        DB::table('currencies')->insert([
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'precision' => 2
            ],

            [
                'code' => 'KES',
                'name' => 'Kenyan Shilling',
                'symbol' => 'KSh',
                'precision' => 2
            ],

            [
                'code' => 'SOS',
                'name' => 'Somali Shilling',
                'symbol' => 'SOS',
                'precision' => 2
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};