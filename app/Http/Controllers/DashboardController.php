<?php

namespace App\Http\Controllers;

use App\Models\{Client, Invoice, Encaissement, Subscription};
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'clients_total'        => Client::count(),
            'clients_actifs'       => Client::where('status', 'client')->count(),
            'clients_suspendus'    => Client::where('is_suspended', true)->count(),
            'prospects_total'      => Client::where('status', 'prospect')->count(),
            'invoices_pending'     => Invoice::where('status', 'brouillon')->count(),
            'invoices_locked'      => Invoice::where('is_locked', true)->count(),
            'encaissements_today'  => Encaissement::whereDate('payment_date', today())->where('status', 'valide')->sum('amount'),
            'encaissements_month'  => Encaissement::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->where('status', 'valide')->sum('amount'),
            'subscriptions_active' => Subscription::where('status', 'active')->count(),
            'subscriptions_expiring' => Subscription::where('status', 'active')->whereBetween('current_cycle_end', [now(), now()->addDays(7)])->count(),
            'recent_invoices'      => Invoice::with('client')->latest()->limit(8)->get(),
            'recent_encaissements' => Encaissement::with(['client', 'creator'])->latest()->limit(5)->get(),
        ]);
    }

    public function dailyTotal(): JsonResponse
    {
        $total = Encaissement::whereDate('payment_date', today())->where('status', 'valide')->sum('amount');
        return response()->json(['total' => $total, 'date' => today()]);
    }
}
