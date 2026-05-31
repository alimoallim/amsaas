<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('sale_agreement_id')->constrained('sale_agreements')->onDelete('cascade');
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('principal', 12, 2)->default(0);
            $table->decimal('interest', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('pending'); // pending | partially_paid | paid | overdue
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique combination index
            $table->unique(['sale_agreement_id', 'installment_number'], 'uq_installment_number');

            // Standard Indexes
            $table->index(['sale_agreement_id', 'status'], 'idx_installment_agreement_status');
            $table->index(['due_date', 'status'], 'idx_installment_due_status');
        });

        // Specialized Check Constraints
        DB::statement('ALTER TABLE installment_schedules ADD CONSTRAINT chk_installment_amount CHECK (amount > 0)');
        DB::statement('ALTER TABLE installment_schedules ADD CONSTRAINT chk_installment_paid_amount CHECK (paid_amount >= 0)');
        DB::statement('ALTER TABLE installment_schedules ADD CONSTRAINT chk_paid_not_exceed CHECK (paid_amount <= amount)');
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_schedules');
    }
};
