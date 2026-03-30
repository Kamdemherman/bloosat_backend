<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::with(['commercial', 'sites'])
            ->withCount(['sites', 'invoices', 'subscriptions']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->search}%")
                  ->orWhere('prenom', 'like', "%{$request->search}%")
                  ->orWhere('raison_sociale', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'              => 'required|string|max:255',
            'prenom'           => 'nullable|string|max:255',
            'raison_sociale'   => 'nullable|string|max:255',
            'nature'           => 'required|in:physique,morale',
            'type'             => 'required|in:ordinaire,grand_compte',
            'email'            => 'nullable|email|max:255',
            'telephone'        => 'nullable|string|max:20',
            'adresse'          => 'nullable|string|max:500',
            'ville'            => 'nullable|string|max:100',
            'pays'             => 'nullable|string|max:100',
            'ninea'            => 'nullable|string|max:50',
            'rccm'             => 'nullable|string|max:50',
            'commercial_id'    => 'nullable|exists:users,id',
            'commercial_email' => 'nullable|email|max:255',
            'notes'            => 'nullable|string',
        ]);

        $client = Client::create($validated);

        return response()->json($client->load('commercial'), 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(
            $client->load(['commercial', 'sites', 'invoices', 'subscriptions', 'priceOverrides.product'])
        );
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'nom'              => 'sometimes|required|string|max:255',
            'prenom'           => 'nullable|string|max:255',
            'raison_sociale'   => 'nullable|string|max:255',
            'nature'           => 'sometimes|in:physique,morale',
            'type'             => 'sometimes|in:ordinaire,grand_compte',
            'email'            => 'nullable|email|max:255',
            'telephone'        => 'nullable|string|max:20',
            'adresse'          => 'nullable|string|max:500',
            'ville'            => 'nullable|string|max:100',
            'commercial_id'    => 'nullable|exists:users,id',
            'commercial_email' => 'nullable|email|max:255',
            'notes'            => 'nullable|string',
        ]);

        $client->update($validated);

        return response()->json($client->fresh('commercial'));
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->isClient()) {
            return response()->json([
                'message' => 'Les clients ne peuvent pas être supprimés, seulement désactivés.',
            ], 422);
        }

        $client->delete();

        return response()->json(['message' => 'Prospect supprimé avec succès.']);
    }

    public function deactivate(Client $client): JsonResponse
    {
        $client->update(['status' => 'inactif']);
        return response()->json(['message' => 'Client désactivé.']);
    }

    public function suspend(Client $client): JsonResponse
    {
        $client->suspend();
        return response()->json(['message' => 'Client suspendu.']);
    }

    public function unsuspend(Client $client): JsonResponse
    {
        $client->unsuspend();
        return response()->json(['message' => 'Client réactivé.']);
    }
}
