<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['client', 'invoice'])->latest();

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('client_id')) $query->where('client_id', $request->client_id);
        if ($request->filled('expiring')) {
            $query->where('current_cycle_end', '<=', now()->addDays((int) $request->expiring));
        }

        return response()->json($query->paginate(20));
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return response()->json(
            $subscription->load(['client', 'invoice.items.product', 'redevances.invoice'])
        );
    }
}
