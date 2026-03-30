<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::withTrashed();

        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('type'))     $query->where('type', $request->type);
        if ($request->filled('search'))   $query->where('name', 'like', "%{$request->search}%");
        if (! $request->boolean('trashed')) $query->withoutTrashed();

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'required|in:produit,service',
            'type'        => 'required_if:category,service|nullable|in:renouvelable,non_renouvelable',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'tax_rate'    => 'nullable|numeric|min:0|max:100',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        return response()->json(Product::create($validated), 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if ($product->isUsedInDefinitiveInvoice()) {
            return response()->json([
                'message' => 'Ce produit ne peut pas être modifié car il est déjà utilisé dans une facture définitive.',
            ], 422);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'tax_rate'    => 'nullable|numeric|min:0|max:100',
            'is_active'   => 'nullable|boolean',
        ]);

        $product->update($validated);

        return response()->json($product->fresh());
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->isUsedInDefinitiveInvoice()) {
            return response()->json([
                'message' => 'Ce produit ne peut pas être supprimé car il est déjà utilisé dans une facture définitive.',
            ], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Produit mis à la corbeille.']);
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return response()->json(['message' => 'Produit restauré.', 'product' => $product]);
    }
}
