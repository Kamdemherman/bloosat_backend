<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email = $request->email;
        $password = $request->password;
        
        \Log::info('[AuthController] Login attempt', [
            'email' => $email,
            'password_sent' => $password,
            'password_hash' => sha1($password),
        ]);

        $user = User::with('role')->where('email', $email)->first();
        
        if ($user) {
            \Log::info('[AuthController] User found', [
                'email' => $user->email,
                'password_stored' => $user->password,
                'match' => sha1($password) === $user->password,
            ]);
        } else {
            \Log::warning('[AuthController] User not found: ' . $email);
        }

        if (! $user || sha1($password) !== $user->password) {
            \Log::warning('[AuthController] Login failed');
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Compte désactivé. Contactez l\'administrateur.'], 403);
        }

        $token = $user->createToken('bss-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role'));
    }
}
