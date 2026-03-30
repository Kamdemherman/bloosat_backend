<?php

namespace App\Http\Controllers;

use App\Models\{StockMovement, StockItem, Warehouse};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['product', 'fromWarehouse', 'toWarehouse', 'creator', 'site'])
            ->latest('movement_date');

        if ($request->filled('type'))         $query->where('type', $request->type);
        if ($request->filled('product_id'))   $query->where('product_id', $request->product_id);
        if ($request->filled('warehouse_id')) {
            $wid = $request->warehouse_id;
            $query->where(fn ($q) =>
                $q->where('from_warehouse_id', $wid)->orWhere('to_warehouse_id', $wid)
            );
        }

        return response()->json($query->paginate(20));
    }

    public function storeMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'        => 'required|exists:products,id',
            'type'              => 'required|in:entree,sortie,transfert,installation,retour',
            'quantity'          => 'required|numeric|min:0.01',
            'reason'            => 'required|string|max:500',
            'movement_date'     => 'required|date',
            'from_warehouse_id' => 'required_unless:type,entree|nullable|exists:warehouses,id',
            'to_warehouse_id'   => 'required_unless:type,sortie,installation|nullable|exists:warehouses,id',
            'site_id'           => 'nullable|exists:sites,id',
        ]);

        $movement = StockMovement::create(array_merge($validated, [
            'created_by' => $request->user()->id,
        ]));

        return response()->json(
            $movement->load(['product', 'fromWarehouse', 'toWarehouse', 'site']),
            201
        );
    }

    public function destroy(StockMovement $movement): JsonResponse
    {
        DB::transaction(function () use ($movement) {
            $movement->reverseStockLevels();
            $movement->delete();
        });

        return response()->json(['message' => 'Mouvement de stock supprimé.']);
    }

    public function levels(Request $request): JsonResponse
    {
        $query = StockItem::with(['product', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        return response()->json($query->get());
    }

    public function warehouses(): JsonResponse
    {
        return response()->json(Warehouse::where('is_active', true)->get());
    }
}
