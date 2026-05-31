<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function login(
        Request $request
    ): JsonResponse {

        $validated =
            $request->validate([

                'email' =>

                    ['required', 'email'],

                'password' =>

                    ['required'],
            ]);

        $user =
            User::where(

                'email',

                $validated['email']

            )->first();

        /*
        |--------------------------------------------------------------------------
        | Invalid Credentials
        |--------------------------------------------------------------------------
        */

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid credentials',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Inactive User
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            return response()->json([

                'success' => false,

                'message' =>
                    'User account disabled',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Token
        |--------------------------------------------------------------------------
        */

        $token =
            $user
                ->createToken(
                    'auth_token'
                )
                ->plainTextToken;

        return response()->json([

            'success' => true,

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
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */

    public function me(
        Request $request
    ): JsonResponse {

        return response()->json([

            'success' => true,

            'user' =>
                $request->user(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(
        Request $request
    ): JsonResponse {

        $request
            ->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([

            'success' => true,

            'message' =>
                'Logged out successfully',
        ]);
    }
}