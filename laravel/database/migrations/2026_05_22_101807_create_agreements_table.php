<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'agreements',

            function (
                Blueprint $table
            ) {

                /*
                |--------------------------------------------------------------------------
                | Primary Key
                |--------------------------------------------------------------------------
                */

                $table->uuid('id')

                    ->primary()

                    ->default(
                        DB::raw(
                            'gen_random_uuid()'
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Multi-Tenant Isolation
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid('company_id')

                    ->constrained('companies')

                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Agreement Identity
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'agreement_number',
                    100
                )->unique();

                /*
                |--------------------------------------------------------------------------
                | Agreement Type
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'agreement_type',
                    30
                );

                /*
                |--------------------------------------------------------------------------
                | Core Relationships
                |--------------------------------------------------------------------------
                */

                $table->foreignUuid('apartment_id')

                    ->constrained('apartments')

                    ->cascadeOnDelete();

                $table->foreignUuid('tenant_id')

                    ->constrained('tenants')

                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Lifecycle Management
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status',
                    30
                )->default('draft');

                /*
                |--------------------------------------------------------------------------
                | Agreement Dates
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'start_date'
                );

                $table->date(
                    'end_date'
                )->nullable();

                $table->timestamp(
                    'signed_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Financial Overview
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'contract_amount',
                    14,
                    2
                )->nullable();

                $table->string(
                    'currency',
                    10
                )->default('USD');

                /*
                |--------------------------------------------------------------------------
                | Approval Workflow
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'approved_by'
                )->nullable();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Termination Workflow
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'terminated_at'
                )->nullable();

                $table->uuid(
                    'terminated_by'
                )->nullable();

                $table->text(
                    'termination_reason'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Document Management
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'contract_file_path',
                    500
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'created_by'
                )->nullable();

                $table->uuid(
                    'updated_by'
                )->nullable();

                $table->timestamps();

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [

                        'company_id',

                        'agreement_type',
                    ],

                    'idx_agreements_company_type'
                );

                $table->index(
                    [

                        'company_id',

                        'status',
                    ],

                    'idx_agreements_company_status'
                );

                $table->index(
                    [

                        'apartment_id',

                        'status',
                    ],

                    'idx_agreements_apartment_status'
                );

                $table->index(
                    [

                        'tenant_id',

                        'status',
                    ],

                    'idx_agreements_tenant_status'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Agreement Type Constraint
        |--------------------------------------------------------------------------
        */

        DB::statement(

            "
            ALTER TABLE agreements

            ADD CONSTRAINT chk_agreement_type

            CHECK (

                agreement_type IN (

                    'rental',

                    'sale'
                )
            )
            "
        );

        /*
        |--------------------------------------------------------------------------
        | Agreement Status Constraint
        |--------------------------------------------------------------------------
        */

        DB::statement(

            "
            ALTER TABLE agreements

            ADD CONSTRAINT chk_agreement_status

            CHECK (

                status IN (

                    'draft',

                    'pending_approval',

                    'approved',

                    'active',

                    'completed',

                    'terminated',

                    'cancelled',

                    'expired',

                    'renewal_pending',

                    'renewed',

                    'defaulted'
                )
            )
            "
        );

        /*
        |--------------------------------------------------------------------------
        | Financial Integrity
        |--------------------------------------------------------------------------
        */

        DB::statement(

            "
            ALTER TABLE agreements

            ADD CONSTRAINT chk_contract_amount

            CHECK (

                contract_amount IS NULL

                OR

                contract_amount >= 0
            )
            "
        );

        /*
        |--------------------------------------------------------------------------
        | Date Integrity
        |--------------------------------------------------------------------------
        */

        DB::statement(

            "
            ALTER TABLE agreements

            ADD CONSTRAINT chk_agreement_dates

            CHECK (

                end_date IS NULL

                OR

                end_date >= start_date
            )
            "
        );

        /*
        |--------------------------------------------------------------------------
        | Active Agreement Guard
        |--------------------------------------------------------------------------
        |
        | Prevents multiple active agreements
        | on same apartment.
        |--------------------------------------------------------------------------
        */

        DB::statement(

            "
            CREATE UNIQUE INDEX

            uq_agreements_apartment_active

            ON agreements (

                apartment_id
            )

            WHERE

                status = 'active'

                AND deleted_at IS NULL
            "
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'agreements'
        );
    }
};