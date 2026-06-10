<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;

use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Company + First Admin
    |--------------------------------------------------------------------------
    */

    public function registerCompany(
        Request $request
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Prevent Multiple Bootstrap
        |--------------------------------------------------------------------------
        */

        

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
    $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        'company_name' =>

            ['required', 'string', 'max:255'],

        'company_email' =>

            ['required', 'email', 'unique:companies,email'],

        'company_phone' =>

            ['nullable', 'string', 'max:50'],

        'company_address' =>

            ['nullable', 'string'],

        'company_city' =>

            ['nullable', 'string', 'max:255'],

        'company_country' =>

            ['nullable', 'string', 'max:255'],

        'registration_number' =>

            ['nullable', 'string', 'max:255'],

        'tax_number' =>

            ['nullable', 'string', 'max:255'],

        /*
        |--------------------------------------------------------------------------
        | First Admin User
        |--------------------------------------------------------------------------
        */

        'name' =>

            ['required', 'string', 'max:255'],

        'email' =>

            ['required', 'email', 'unique:users,email'],

        'password' =>

            ['required', 'min:6'],
    ]);

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Create Company
            |--------------------------------------------------------------------------
            */

           $company =
    Company::create([

        'name' =>

            $validated['company_name'],

        'email' =>

            $validated['company_email'],

        'phone' =>

            $validated['company_phone'] ?? null,

        'address' =>

            $validated['company_address'] ?? null,

        'city' =>

            $validated['company_city'] ?? null,

        'country' =>

            $validated['company_country'] ?? null,

        'registration_number' =>

            $validated['registration_number'] ?? null,

        'tax_number' =>

            $validated['tax_number'] ?? null,

        'currency_code' =>

            'USD',

        'is_active' =>

            true,
    ]);

            /*
            |--------------------------------------------------------------------------
            | Create First Company Admin
            |--------------------------------------------------------------------------
            */

            $user =
                User::create([

                    'company_id' =>
                        $company->id,

                    'name' =>
                        $validated['name'],

                    'email' =>
                        $validated['email'],

                    'password' =>
                        Hash::make(
                            $validated['password']
                        ),

                    'role' =>
                        'company_admin',

                    'is_active' =>
                        true,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Generate Sanctum Token
            |--------------------------------------------------------------------------
            */

            app(ChartOfAccountsService::class)->seedDefaults($company, $user->id);

            $token =
                $user
                    ->createToken(
                        'auth_token'
                    )
                    ->plainTextToken;

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' =>
                    'Company registered successfully.',

                'token' => $token,

                'user' => [

                    'id' =>
                        $user->id,

                    'company_id' =>
                        $user->company_id,

                    'name' =>
                        $user->name,

                    'email' =>
                        $user->email,

                    'role' =>
                        $user->role,
                ],

                'company' => [

                    'id' =>
                        $company->id,

                    'name' =>
                        $company->name,

                    'email' =>
                        $company->email,
                ]
            ]);
        }
        catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}