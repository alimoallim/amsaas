<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\Company;

use App\Http\Requests\Api\V1\StoreCompanyRequest;

use App\Http\Requests\Api\V1\UpdateCompanyRequest;

use App\Http\Resources\Api\V1\CompanyResource;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CompanyController extends Controller
{
    use AuthorizesRequests;

    /**
     * List the authenticated user's company only.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->where('id', auth()->user()->company_id)
            ->latest()
            ->paginate(15);

        return CompanyResource::collection(
            $companies
        );
    }

    /**
     * Store company
     */
    public function store(
        StoreCompanyRequest $request
    ): JsonResponse
    {
        $data = $request->validated();

        // Handle Logo Upload
        if ($request->hasFile('logo')) {

            $path = $request->file('logo')->store(
                'company-logos',
                'public'
            );

            $data['logo_path'] = $path;
        }

        $company = Company::create($data);

        return response()->json([

            'success' => true,

            'message' =>
                'Company created successfully.',

            'data' =>
                new CompanyResource($company)

        ], 201);
    }

    /**
     * Show company
     */
    public function show(
        Company $company
    ): CompanyResource
    {
        $this->authorize('view', $company);

        return new CompanyResource(
            $company
        );
    }

    /**
     * Update company
     */
    public function update(
        UpdateCompanyRequest $request,
        Company $company
    ): JsonResponse
    {
        $this->authorize('update', $company);

        $data = $request->validated();

        // Optional Logo Update
        if ($request->hasFile('logo')) {

            $path = $request->file('logo')->store(
                'company-logos',
                'public'
            );

            $data['logo_path'] = $path;
        }

        $company->update($data);

        return response()->json([

            'success' => true,

            'message' =>
                'Company updated successfully.',

            'data' =>
                new CompanyResource($company)

        ]);
    }

    /**
     * Delete company
     */
    public function destroy(
        Company $company
    ): JsonResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return response()->json([

            'success' => true,

            'message' =>
                'Company deleted successfully.'

        ]);
    }
}