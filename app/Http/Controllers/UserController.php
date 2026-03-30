<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::with('role')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])/',
            ],
            'role_id' => 'required|exists:roles,id',
            'phone'   => 'nullable|string|max:20',
        ], [
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
        ]);

        $validated['password'] = sha1($validated['password']);
        unset($validated['password_confirmation']);

        return response()->json(User::create($validated)->load('role'), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('role'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'email'     => 'sometimes|required|email|unique:users,email,' . $user->id,
            'role_id'   => 'sometimes|required|exists:roles,id',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'password'  => [
                'nullable', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])/',
            ],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = sha1($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['password_confirmation']);
        $user->update($validated);

        return response()->json($user->fresh('role'));
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Vous ne pouvez pas désactiver votre propre compte.'], 422);
        }

        $user->update(['is_active' => false]);

        return response()->json(['message' => 'Utilisateur désactivé.']);
    }
}
