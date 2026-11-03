<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CourierController extends Controller
{
    /**
     * Display a listing of couriers.
     * Supports search (?search=budi+agung), level filter (?level=2,3),
     * dynamic sorting (?sort_by=registered_at&sort_dir=desc), and pagination (?per_page=15).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        $couriers = Courier::query()
            ->search($request->input('search'))
            ->byLevels($request->input('level'))
            ->sort(
                $request->input('sort_by', 'name'),
                $request->input('sort_dir', 'asc')
            )
            ->paginate($perPage);

        return CourierResource::collection($couriers);
    }

    /**
     * Store a newly created courier in storage.
     */
    public function store(StoreCourierRequest $request): JsonResponse
    {
        $courier = Courier::create($request->validated());

        return (new CourierResource($courier))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified courier.
     */
    public function show(Courier $courier): CourierResource
    {
        return new CourierResource($courier);
    }

    /**
     * Update the specified courier in storage.
     */
    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->validated());

        return new CourierResource($courier);
    }

    /**
     * Remove the specified courier from storage.
     */
    public function destroy(Courier $courier): JsonResponse
    {
        $courier->delete();

        return response()->json([
            'message' => 'Courier deleted successfully',
        ], Response::HTTP_OK);
    }
}
