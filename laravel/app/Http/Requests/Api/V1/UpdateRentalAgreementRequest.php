<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesAgreementBilling;
use App\Models\Agreement;
use App\Models\Apartment;
use App\Models\RentalAgreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateRentalAgreementRequest extends FormRequest
{
    use ValidatesAgreementBilling;
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return auth::check();
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
            | Core Agreement Relationships
            |--------------------------------------------------------------------------
            */
            'apartment_id' => [
                'sometimes',
                'uuid',
                Rule::exists('apartments', 'id')->where(
                    fn ($query) => $query->where('company_id', auth::user()->company_id)
                ),
            ],
            'tenant_id' => [
                'sometimes',
                'uuid',
                Rule::exists('tenants', 'id')->where(
                    fn ($query) => $query->where('company_id', auth::user()->company_id)
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Agreement Dates & Status
            |--------------------------------------------------------------------------
            */
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'signed_at' => ['nullable', 'date'],

            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Agreement::STATUS_DRAFT,
                    Agreement::STATUS_PENDING_APPROVAL,
                    Agreement::STATUS_APPROVED,
                    Agreement::STATUS_ACTIVE,
                    Agreement::STATUS_TERMINATED,
                    Agreement::STATUS_COMPLETED,
                    Agreement::STATUS_CANCELLED,
                    Agreement::STATUS_EXPIRED,
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Financial Terms
            |--------------------------------------------------------------------------
            */
            'monthly_rent'     => ['sometimes', 'numeric', 'gt:0'],
            'security_deposit' => ['nullable', 'numeric', 'min:0'],
            'contract_amount'  => ['nullable', 'numeric', 'min:0'],
            'currency'         => ['nullable', 'string', 'max:10'],

            /*
            |--------------------------------------------------------------------------
            | Payment Configuration
            |--------------------------------------------------------------------------
            */
            'payment_due_day' => ['sometimes', 'integer', 'between:1,28'],

            /*
            |--------------------------------------------------------------------------
            | Utilities
            |--------------------------------------------------------------------------
            */
            'includes_water'       => ['nullable', 'boolean'],
            'includes_electricity' => ['nullable', 'boolean'],
            'includes_internet'    => ['nullable', 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | Renewal Policy
            |--------------------------------------------------------------------------
            */
            'auto_renew'          => ['nullable', 'boolean'],
            'renewal_notice_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            /*
            |--------------------------------------------------------------------------
            | Audit Security
            |--------------------------------------------------------------------------
            */
            'company_id'       => ['prohibited'],
            'agreement_number' => ['prohibited'],
            'agreement_type'   => ['prohibited'],
            'approved_by'      => ['prohibited'],
            'approved_at'      => ['prohibited'],
            'terminated_by'    => ['prohibited'],
            'terminated_at'    => ['prohibited'],
            'created_by'       => ['prohibited'],
            'updated_by'       => ['prohibited'],

            /*
            |--------------------------------------------------------------------------
            | Files & Notes
            |--------------------------------------------------------------------------
            */
            'contract_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'notes'         => ['nullable', 'string'],
            'special_terms' => ['nullable', 'string'],
        ], $this->agreementBillingRules());
    }

    /*
    |--------------------------------------------------------------------------
    | Business Rule Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator($validator): void
    {
        $this->validateAgreementBillingRows($validator);

        $validator->after(function ($validator) {
            $rental = $this->resolveRentalAgreement();
            if (! $rental?->agreement) {
                return;
            }

            $agreement = $rental->agreement;

            if ($this->filled('end_date')) {
                $startDate = $this->input('start_date') ?? $agreement->start_date?->toDateString();
                if (
                    $startDate
                    && \Illuminate\Support\Carbon::parse($this->end_date)->lte(
                        \Illuminate\Support\Carbon::parse($startDate)
                    )
                ) {
                    $validator->errors()->add('end_date', 'End date must be after start date.');
                }
            }

            // 1. Active Agreements Restrictions
            if ($agreement->status === Agreement::STATUS_ACTIVE) {
                $restrictedFields = ['apartment_id', 'tenant_id', 'start_date'];
                foreach ($restrictedFields as $field) {
                    if ($this->has($field)) {
                        $validator->errors()->add($field, 'This field cannot be modified after agreement activation.');
                    }
                }
            }

            // 2. Prevent Illegal Status Transitions
            if ($this->has('status') && $agreement->status !== $this->status) {
                $finalizedStatuses = [
                    Agreement::STATUS_TERMINATED,
                    Agreement::STATUS_COMPLETED,
                    Agreement::STATUS_CANCELLED,
                ];

                if (in_array($agreement->status, $finalizedStatuses, true)) {
                    $validator->errors()->add('status', 'Cannot change status of a finalized agreement.');
                }
            }

            // 3. Prevent modification of Terminated/Completed/Cancelled
            if (in_array($agreement->status, [
                Agreement::STATUS_TERMINATED,
                Agreement::STATUS_COMPLETED,
                Agreement::STATUS_CANCELLED,
            ], true)) {
                $validator->errors()->add('agreement', 'This agreement can no longer be modified.');
            }

            // 4. Apartment Validation
            if ($this->filled('apartment_id')) {
                $apartment = Apartment::query()
                    ->where('id', $this->apartment_id)
                    ->where('company_id', auth::user()->company_id)
                    ->first();

                if ($apartment) {
                    if (!in_array($apartment->listing_type, [Apartment::LISTING_TYPE_RENTAL, 'hybrid'])) {
                        $validator->errors()->add('apartment_id', 'Selected apartment is not rental-enabled.');
                    }
                    if ($apartment->inventory_status === Apartment::STATUS_SOLD) {
                        $validator->errors()->add('apartment_id', 'Sold apartments cannot have rental agreements.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'monthly_rent.gt'         => 'Monthly rent must be greater than zero.',
            'payment_due_day.between' => 'Payment due day must be between 1 and 28.',
            'end_date.after'          => 'End date must be after start date.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeRecurringChargesInput();

        $merge = [
            'currency' => $this->currency ? strtoupper($this->currency) : null,
            'security_deposit' => filled($this->security_deposit) ? trim((string) $this->security_deposit) : null,
        ];

        if ($this->has('auto_renew')) {
            $merge['auto_renew'] = filter_var($this->auto_renew, FILTER_VALIDATE_BOOLEAN);
        }

        foreach (['includes_water', 'includes_electricity', 'includes_internet'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = filter_var($this->{$field}, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $this->merge($merge);
    }

    protected function resolveRentalAgreement(): ?RentalAgreement
    {
        $id = $this->route('rental_agreement');

        if (! $id) {
            return null;
        }

        return RentalAgreement::query()
            ->with('agreement')
            ->whereHas(
                'agreement',
                fn ($query) => $query->where('company_id', auth()->user()->company_id)
            )
            ->where('id', $id)
            ->first();
    }
}