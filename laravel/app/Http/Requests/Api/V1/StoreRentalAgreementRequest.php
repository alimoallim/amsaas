<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesAgreementBilling;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Services\Property\ApartmentInventoryService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalAgreementRequest extends FormRequest
{
    use ValidatesAgreementBilling;
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return auth()->check();
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return array_merge([

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            'apartment_id' => [

                'required',

                'uuid',

                Rule::exists(
                    'apartments',
                    'id'
                )->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        auth()->user()->company_id
                    )
                ),
            ],

            'tenant_id' => [

                'required',

                'uuid',

                Rule::exists(
                    'tenants',
                    'id'
                )->where(

                    fn ($query) =>

                    $query->where(

                        'company_id',

                        auth()->user()->company_id
                    )
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Agreement Dates
            |--------------------------------------------------------------------------
            */

            'start_date' => [

                'required',

                'date',
            ],

            'end_date' => [

                'nullable',

                'date',

                'after:start_date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Financial Terms
            |--------------------------------------------------------------------------
            */

            'monthly_rent' => [

                'required',

                'numeric',

                'gt:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment Policy
            |--------------------------------------------------------------------------
            */

            'payment_due_day' => [

                'required',

                'integer',

                'between:1,28',
            ],

            /*
            |--------------------------------------------------------------------------
            | Optional Fields
            |--------------------------------------------------------------------------
            */

            'security_deposit' => [

                'nullable',

                'numeric',

                'min:0',
            ],

            'includes_water' => [
                'nullable',
                'boolean',
            ],

            'includes_electricity' => [
                'nullable',
                'boolean',
            ],

            'includes_internet' => [
                'nullable',
                'boolean',
            ],

            'auto_renew' => [
                'nullable',
                'boolean',
            ],

            'renewal_notice_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],

            'contract_file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'special_terms' => [
                'nullable',
                'string',
            ],

            'notes' => [

                'nullable',

                'string',
            ],

            'status' => [
                'nullable',
                'string',
                Rule::in([
                    Agreement::STATUS_DRAFT,
                    Agreement::STATUS_ACTIVE,
                    Agreement::STATUS_PENDING_APPROVAL,
                    Agreement::STATUS_TERMINATED,
                    Agreement::STATUS_EXPIRED,
                    'pending',
                ]),
            ],
        ], $this->agreementBillingRules());
    }

    /*
    |--------------------------------------------------------------------------
    | Business Rule Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator(
        $validator
    ): void {

        $this->validateAgreementBillingRows($validator);

        $validator->after(

            function ($validator) {

                $apartment = Apartment::query()

                    ->where(
                        'id',
                        $this->apartment_id
                    )

                    ->where(
                        'company_id',
                        auth()->user()->company_id
                    )

                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Apartment Not Found
                |--------------------------------------------------------------------------
                */

                if (! $apartment) {

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Apartment Must Be Rental Enabled
                |--------------------------------------------------------------------------
                */

                if (

                    ! in_array(

                        $apartment->listing_type,

                        [

                            Apartment::LISTING_TYPE_RENTAL,

                            Apartment::LISTING_TYPE_HYBRID,
                        ],
                        true
                    )
                ) {

                    $validator->errors()->add(

                        'apartment_id',

                        'Selected apartment is not rental-enabled.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Apartment Must Not Be Occupied
                |--------------------------------------------------------------------------
                */

                if (

                    $apartment->inventory_status
                    === Apartment::STATUS_OCCUPIED
                ) {

                    $validator->errors()->add(

                        'apartment_id',

                        'Apartment is already occupied.'
                    );
                }

                $inventory = app(ApartmentInventoryService::class);

                if ($inventory->hasConflictingLease($apartment)) {
                    $validator->errors()->add(
                        'apartment_id',
                        'This unit already has a draft or active rental agreement.'
                    );
                }

                $tenant = Tenant::query()
                    ->where('id', $this->tenant_id)
                    ->where('company_id', auth()->user()->company_id)
                    ->first();

                if ($tenant && $tenant->status === 'blacklisted') {
                    $validator->errors()->add(
                        'tenant_id',
                        'Blacklisted tenants cannot be assigned to rental agreements.'
                    );
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'apartment_id.required' =>

                'Apartment selection is required.',

            'tenant_id.required' =>

                'Tenant selection is required.',

            'monthly_rent.gt' =>

                'Monthly rent must be greater than zero.',

            'payment_due_day.between' =>

                'Payment due day must be between 1 and 28.',

            'end_date.after' =>

                'End date must be after start date.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sanitization
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $this->normalizeRecurringChargesInput();

        $status = $this->input('status');
        if ($status === 'pending') {
            $status = Agreement::STATUS_PENDING_APPROVAL;
        }

        $this->merge([
            'status' => filled($status) ? $status : Agreement::STATUS_DRAFT,
            'monthly_rent' =>

                $this->monthly_rent !== null
                    ? trim($this->monthly_rent)
                    : null,

            'security_deposit' =>

                $this->security_deposit !== null
                    ? trim($this->security_deposit)
                    : null,

            'auto_renew' =>
                filter_var(
                    $this->auto_renew,
                    FILTER_VALIDATE_BOOLEAN
                ),

            'includes_water' =>
                filter_var(
                    $this->includes_water,
                    FILTER_VALIDATE_BOOLEAN
                ),

            'includes_electricity' =>
                filter_var(
                    $this->includes_electricity,
                    FILTER_VALIDATE_BOOLEAN
                ),

            'includes_internet' =>
                filter_var(
                    $this->includes_internet,
                    FILTER_VALIDATE_BOOLEAN
                ),
        ]);
    }
}