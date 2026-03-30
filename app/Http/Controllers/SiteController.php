<?php

namespace App\Http\Controllers;

use App\Models\{Site, Client};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Site::with('client')->latest();

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('ville', 'like', "%{$request->search}%");
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate(20));
    }

    public function byClient(Client $client): JsonResponse
    {
        return response()->json(
            $client->sites()->with('client')->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'name'        => 'required|string|max:255',
            'adresse'     => 'nullable|string|max:500',
            'ville'       => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'contact_nom' => 'nullable|string|max:255',
            'contact_tel' => 'nullable|string|max:20',
        ]);

        $site = Site::create($validated);

        return response()->json($site->load('client'), 201);
    }

    public function show(Site $site): JsonResponse
    {
        return response()->json($site->load('client'));
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'adresse'     => 'nullable|string|max:500',
            'ville'       => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'contact_nom' => 'nullable|string|max:255',
            'contact_tel' => 'nullable|string|max:20',
            'is_active'   => 'nullable|boolean',
        ]);

        $site->update($validated);

        return response()->json($site->fresh('client'));
    }

    public function destroy(Site $site): JsonResponse
    {
        $site->update(['is_active' => false]);

        return response()->json(['message' => 'Site désactivé avec succès.']);
    }

    public function restore(Site $site): JsonResponse
    {
        $site->update(['is_active' => true]);

        return response()->json(['message' => 'Site réactivé avec succès.']);
    }
}

