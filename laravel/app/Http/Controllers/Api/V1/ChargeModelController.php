<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ChargeModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChargeModelStoreRequest;
use App\Http\Requests\Api\V1\ChargeModelUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Resources\Api\V1\ChargeModelResource;
use App\Services\Billing\ChargeModelVersionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ChargeModelController extends Controller
{
    use AuthorizesRequests;

    public function index(
        Request $request
    ) {
        $this->authorize('viewAny', ChargeModel::class);

        $query =
            ChargeModel::query()

                ->with(
                    'chargeType'
                );

        if (
            $request->filled(
                'search'
            )
        ) {

            $search =
                $request->search;

            $query->where(

                fn ($q) =>

                $q->where(
                    'name',
                    'ILIKE',
                    "%{$search}%"
                )

                ->orWhere(
                    'code',
                    'ILIKE',
                    "%{$search}%"
                )
            );
        }

        if (
            $request->filled(
                'status'
            )
        ) {

            $query->where(
                'status',
                $request->status
            );
        }

        if (
            $request->filled(
                'pricing_strategy'
            )
        ) {

            $query->where(
                'pricing_strategy',
                $request->pricing_strategy
            );
        }

        return ChargeModelResource::collection(

            $query

                ->latest()

                ->paginate(
                    $request->integer(
                        'per_page',
                        15
                    )
                )
        );
    }

    public function store(
        
    ChargeModelStoreRequest $request

    ): JsonResponse {
        $this->authorize('create', ChargeModel::class);

        $validated = $request->validated();

        DB::beginTransaction();

        try {

            $chargeModel =
                ChargeModel::create([

                    ...$validated,

                    'id' =>
                        (string)
                        Str::uuid(),

                    'company_id' =>
                        Auth::user()
                            ->company_id,

                    'created_by' =>
                        Auth::id(),
                ]);

            DB::commit();

            return response()->json([

                'message' =>
                    'Charge model created successfully.',

                'data' =>
                    new ChargeModelResource(
                        $chargeModel
                            ->load(
                                'chargeType'
                            )
                    ),
            ], 201);
        }

        catch (
            \Throwable $exception
        ) {

            DB::rollBack();

            throw $exception;
        }
    }

    public function show(
        ChargeModel $chargeModel
    ): ChargeModelResource {
        $this->authorize('view', $chargeModel);

        return new ChargeModelResource(

            $chargeModel->load(
                'chargeType'
            )
        );
    }

    public function update(
        ChargeModelUpdateRequest $request,
        ChargeModel $chargeModel,
        ChargeModelVersionService $versions,
    ): JsonResponse {
        $this->authorize('update', $chargeModel);

        $validated = $request->validated();

        if ($versions->shouldVersion($chargeModel)) {
            $chargeModel = $versions->createVersion($chargeModel, $validated, $request->user());

            return response()->json([
                'message' => 'A new charge model version was created. The previous version stays effective until the day before the new start date.',
                'versioned' => true,
                'previous_id' => $chargeModel->metadata['replaces_id'] ?? null,
                'data' => new ChargeModelResource(
                    $chargeModel->load('chargeType')
                ),
            ]);
        }

        $chargeModel->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Charge model updated successfully.',
            'versioned' => false,
            'data' => new ChargeModelResource(
                $chargeModel->fresh()->load('chargeType')
            ),
        ]);
    }

    public function clone(
        ChargeModel $chargeModel,
        ChargeModelVersionService $versions,
    ): JsonResponse {
        $this->authorize('view', $chargeModel);

        $copy = $versions->cloneAsDraft($chargeModel, request()->user());

        return response()->json([
            'message' => 'Charge model cloned as draft.',
            'data' => new ChargeModelResource($copy->load('chargeType')),
        ], 201);
    }

    public function destroy(
        ChargeModel $chargeModel
    ): JsonResponse {
        $this->authorize('delete', $chargeModel);

        $chargeModel->delete();

        return response()->json([

            'message' =>
                'Charge model deleted successfully.',
        ]);
    }
}