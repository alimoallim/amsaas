<?php

namespace App\Http\Requests\Api\V1;

use App\Services\Accounting\PostingRuleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'tenant_id' => [
                'required',
                'uuid',
                Rule::exists('tenants', 'id')->where('company_id', $companyId),
            ],
            'payment_purpose' => [
                'nullable',
                'string',
                Rule::in(['rent', 'security_deposit', 'deposit_refund']),
            ],
            'agreement_id' => [
                'required_if:payment_purpose,security_deposit,deposit_refund',
                'nullable',
                'uuid',
                Rule::exists('agreements', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->where('agreement_type', 'rental');
                }),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'bank_transfer', 'mobile_money', 'cheque'])],
            'receipt_account_code' => [
                'nullable',
                'string',
                Rule::in(PostingRuleService::RECEIPT_ACCOUNT_CODES),
                Rule::exists('accounts', 'code')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
