<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class ChargeModelUpdateRequest
    extends ChargeModelStoreRequest
{
    public function rules(): array
    {
        $rules =
            parent::rules();

        $chargeModel =
            $this->route(
                'charge_model'
            );

        $rules['code'] = [

            'required',

            'string',

            'max:100',

            Rule::unique(
                'charge_models',
                'code'
            )

            ->ignore(
                $chargeModel?->id
            )

            ->where(

                fn ($query) =>

                $query->where(

                    'company_id',

                    auth()->user()
                        ->company_id
                )
            ),
        ];

        return $rules;
    }
}