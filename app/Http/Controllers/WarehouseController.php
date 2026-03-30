<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Warehouse::withCount('stockItems')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:warehouses,name',
            'location' => 'required|string|max:255',
        ]);

        return response()->json(Warehouse::create($validated), 201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        return response()->json($warehouse->load('stockItems.product'));
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'location'  => 'sometimes|required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $warehouse->update($validated);

        return response()->json($warehouse->fresh());
    }
}
