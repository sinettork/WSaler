<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('name')->get();

        return UnitResource::collection($units);
    }

    public function store(StoreUnitRequest $request)
    {
        $data = $request->validated();
        if (!empty($data['base'])) {
            $data['conversion_factor_to_base'] = 1;
        }

        $unit = Unit::create($data);

        return (new UnitResource($unit))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $data = $request->validated();
        if (!empty($data['base'])) {
            $data['conversion_factor_to_base'] = 1;
        }

        $unit->update($data);

        return new UnitResource($unit);
    }

    public function destroy(Request $request, Unit $unit)
    {
        if ($unit->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete unit referenced by products.',
            ], 422);
        }

        $unit->delete();

        return response()->noContent();
    }
}
