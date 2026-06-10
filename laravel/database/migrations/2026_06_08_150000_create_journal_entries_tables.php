<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('entry_number', 40);
            $table->date('entry_date');
            $table->date('posting_date');
            $table->string('currency_code', 3)->default('USD');
            $table->string('description', 500);
            $table->string('source_type', 80);
            $table->uuid('source_id')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->unsignedTinyInteger('fiscal_month')->nullable();
            $table->decimal('total_debit', 14, 4)->default(0);
            $table->decimal('total_credit', 14, 4)->default(0);
            $table->string('status', 20)->default('posted');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'entry_number']);
            $table->unique(['company_id', 'source_type', 'source_id']);
            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'fiscal_year', 'fiscal_month']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE journal_entries ADD CONSTRAINT chk_journal_balanced CHECK (total_debit = total_credit AND total_debit > 0)');
        }

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit_amount', 14, 4)->default(0);
            $table->decimal('credit_amount', 14, 4)->default(0);
            $table->string('description', 500)->nullable();
            $table->unsignedSmallInteger('line_order')->default(0);
            $table->timestamps();

            $table->index(['journal_entry_id', 'line_order']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE journal_entry_lines ADD CONSTRAINT chk_journal_line_one_side CHECK (
                    (debit_amount > 0 AND credit_amount = 0) OR
                    (credit_amount > 0 AND debit_amount = 0)
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
