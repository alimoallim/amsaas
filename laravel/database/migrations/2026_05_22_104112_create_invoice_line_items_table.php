<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('monthly_invoice_id')->constrained('monthly_invoices')->onDelete('cascade');
            $table->string('line_type', 50); // rent | electricity | water | security_fee | etc...
            $table->string('description', 500);
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 12, 4)->default(0);
            $table->decimal('amount', 12, 2);
            $table->uuid('reference_id')->nullable(); // Polymorphic tie back to the specific fee, reading, etc.
            $table->string('reference_type', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Standard Indexes
            $table->index('monthly_invoice_id', 'idx_line_items_invoice');
            $table->index(['reference_type', 'reference_id'], 'idx_line_items_reference');
            $table->index(['monthly_invoice_id', 'line_type'], 'idx_line_items_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
