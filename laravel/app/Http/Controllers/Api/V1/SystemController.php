<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\Company;

use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Bootstrap Status
    |--------------------------------------------------------------------------
    */

    public function bootstrapStatus(): JsonResponse
    {
        return response()->json([

            'success' => true,

            'has_company' =>

                Company::exists(),
        ]);
    }
}