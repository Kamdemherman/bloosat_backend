<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Role::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'permissions'  => 'nullable|array',
        ]);

        return response()->json(Role::create($validated), 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($role);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|required|string|max:100',
            'permissions'  => 'nullable|array',
        ]);

        $role->update($validated);

        return response()->json($role->fresh());
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->exists()) {
            return response()->json(['message' => 'Ce rôle est assigné à des utilisateurs actifs.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Rôle supprimé.']);
    }
}
