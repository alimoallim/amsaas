<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | ERP Reference
            |--------------------------------------------------------------------------
            */

            $table->string(
                'tenant_code',
                50
            )
            ->nullable()
            ->after('company_id');

            /*
            |--------------------------------------------------------------------------
            | Tenant Classification
            |--------------------------------------------------------------------------
            */

            $table->string(
                'tenant_type',
                30
            )
            ->default('individual')
            ->after('tenant_code');

            /*
            |--------------------------------------------------------------------------
            | Name Refactor
            |--------------------------------------------------------------------------
            */

            $table->string(
                'first_name',
                100
            )
            ->nullable()
            ->after('tenant_type');

            $table->string(
                'middle_name',
                100
            )
            ->nullable()
            ->after('first_name');

            $table->string(
                'last_name',
                100
            )
            ->nullable()
            ->after('middle_name');

            $table->string(
                'display_name',
                255
            )
            ->nullable()
            ->after('last_name');

            $table->string(
                'company_name',
                255
            )
            ->nullable()
            ->after('display_name');

            /*
            |--------------------------------------------------------------------------
            | Contact Expansion
            |--------------------------------------------------------------------------
            */

            $table->string(
                'alternate_phone',
                50
            )
            ->nullable()
            ->after('phone');

            /*
            |--------------------------------------------------------------------------
            | Legal / Government
            |--------------------------------------------------------------------------
            */

            $table->string(
                'passport_number',
                100
            )
            ->nullable()
            ->after('national_id');

            $table->string(
                'tax_number',
                100
            )
            ->nullable()
            ->after('passport_number');

            /*
            |--------------------------------------------------------------------------
            | Demographics
            |--------------------------------------------------------------------------
            */

            $table->string(
                'gender',
                20
            )
            ->nullable()
            ->after('date_of_birth');

            $table->string(
                'occupation',
                150
            )
            ->nullable()
            ->after('gender');

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            $table->string(
                'country',
                100
            )
            ->nullable()
            ->after('occupation');

            $table->string(
                'city',
                100
            )
            ->nullable()
            ->after('country');

            $table->text(
                'address'
            )
            ->nullable()
            ->after('city');

            $table->string(
                'postal_code',
                50
            )
            ->nullable()
            ->after('address');

            /*
            |--------------------------------------------------------------------------
            | Emergency Contact Refactor
            |--------------------------------------------------------------------------
            */

            $table->string(
                'emergency_contact_name',
                255
            )
            ->nullable()
            ->after('postal_code');

            $table->string(
                'emergency_contact_phone',
                50
            )
            ->nullable()
            ->after('emergency_contact_name');

            $table->string(
                'emergency_contact_relationship',
                100
            )
            ->nullable()
            ->after('emergency_contact_phone');

            /*
            |--------------------------------------------------------------------------
            | Status Refactor
            |--------------------------------------------------------------------------
            */

            $table->string(
                'status',
                30
            )
            ->default('active')
            ->after('emergency_contact_relationship');

            /*
            |--------------------------------------------------------------------------
            | Audit Metadata
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid(
                'created_by'
            )
            ->nullable()
            ->after('status')
            ->constrained('users')
            ->nullOnDelete();

            $table->foreignUuid(
                'updated_by'
            )
            ->nullable()
            ->after('created_by')
            ->constrained('users')
            ->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | Indexes
        |--------------------------------------------------------------------------
        */

        Schema::table('tenants', function (Blueprint $table) {

            $table->index(
                ['company_id', 'status'],
                'idx_tenants_company_status'
            );

            $table->index(
                ['company_id', 'phone'],
                'idx_tenants_company_phone'
            );

            $table->index(
                ['company_id', 'email'],
                'idx_tenants_company_email'
            );

            $table->unique(
                ['company_id', 'tenant_code'],
                'uq_tenants_company_code'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            $table->dropColumn([

                'tenant_code',
                'tenant_type',

                'first_name',
                'middle_name',
                'last_name',
                'display_name',

                'company_name',

                'alternate_phone',

                'passport_number',
                'tax_number',

                'gender',
                'occupation',

                'country',
                'city',
                'address',
                'postal_code',

                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',

                'status',

                'created_by',
                'updated_by',
            ]);
        });
    }
};