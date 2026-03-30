<?php

namespace App\Http\Controllers;

use App\Models\{Encaissement, Invoice, Client};
use App\Mail\PaymentReceiptMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class EncaissementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Encaissement::with(['client', 'invoice', 'creator'])->latest();

        if ($request->filled('client_id')) $query->where('client_id', $request->client_id);
        if ($request->filled('date'))      $query->whereDate('payment_date', $request->date);
        if ($request->filled('search'))    $query->where('reference', 'like', "%{$request->search}%");

        $dailyTotal = Encaissement::whereDate('payment_date', today())
            ->where('status', 'valide')->sum('amount');

        $result               = $query->paginate(20)->toArray();
        $result['daily_total'] = $dailyTotal;

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'      => 'required|integer|exists:clients,id',
            'invoice_id'     => 'required|integer|exists:invoices,id',
            'redevance_id'   => 'nullable|integer|exists:redevances,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:especes,virement,cheque,mobile_money,autre',
            'payment_date'   => 'required|date',
            'proof'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes'          => 'nullable|string',
        ]);

        $proofPath = $request->file('proof')->store('proofs', 'private');

        // Charger la facture pour déterminer si paiement complet
        $invoice = Invoice::find($validated['invoice_id']);
        $isComplete = $validated['amount'] >= $invoice->total;

        $enc = Encaissement::create([
            'client_id'      => $validated['client_id'],
            'invoice_id'     => $validated['invoice_id'],
            'redevance_id'   => $validated['redevance_id'] ?? null,
            'amount'         => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date'   => $validated['payment_date'],
            'notes'          => $validated['notes'] ?? null,
            'created_by'     => $request->user()->id,
            'proof_path'     => $proofPath,
            'status'         => 'valide',
            'is_complete'    => $isComplete,
        ]);

        // Envoyer le reçu au client
        if ($enc->client->email) {
            Mail::to($enc->client->email)
                ->send(new PaymentReceiptMail($enc));
        }

        return response()->json($enc->load(['client', 'invoice', 'creator']), 201);
    }

    public function show(Encaissement $encaissement): JsonResponse
    {
        return response()->json($encaissement->load(['client', 'invoice', 'creator']));
    }

    public function sendReceipt(Encaissement $encaissement): JsonResponse
    {
        if ($encaissement->client->email) {
            Mail::to($encaissement->client->email)
                ->send(new PaymentReceiptMail($encaissement));
        }
        return response()->json(['message' => 'Reçu envoyé par mail.']);
    }

    public function cancel(Encaissement $encaissement): JsonResponse
    {
        if ($encaissement->status === 'annule') {
            return response()->json(['message' => 'Cet encaissement est déjà annulé.'], 422);
        }
        $encaissement->update(['status' => 'annule']);
        return response()->json(['message' => 'Encaissement annulé.']);
    }

    public function dailyTotal(): JsonResponse
    {
        $total = Encaissement::whereDate('payment_date', today())
            ->where('status', 'valide')->sum('amount');
        return response()->json(['total' => $total, 'date' => today()->toDateString()]);
    }
}
