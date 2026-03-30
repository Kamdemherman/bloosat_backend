<?php

namespace App\Http\Controllers;

use App\Models\{Invoice, Subscription};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['client', 'creator'])->latest();

        if ($request->filled('type'))      $query->where('type', $request->type);
        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('client_id')) $query->where('client_id', $request->client_id);
        if ($request->filled('search'))    $query->where('number', 'like', "%{$request->search}%");

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'type'                => 'required|in:pro_forma,definitive,redevance',
            'issue_date'          => 'required|date',
            'due_date'            => 'nullable|date|after_or_equal:issue_date',
            'notes'               => 'nullable|string',
            'discount_amount'     => 'nullable|numeric|min:0',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.site_id'     => 'nullable|exists:sites,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.tax_rate'    => 'nullable|numeric|min:0|max:100',
            'items.*.discount'    => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice = DB::transaction(function () use ($validated, $request) {
            $inv = Invoice::create([
                'client_id'       => $validated['client_id'],
                'created_by'      => $request->user()->id,
                'type'            => $validated['type'],
                'status'          => 'brouillon',
                'issue_date'      => $validated['issue_date'],
                'due_date'        => $validated['due_date'] ?? null,
                'notes'           => $validated['notes'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'subtotal'        => 0,
                'tax_amount'      => 0,
                'total'           => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $inv->items()->create([
                    'product_id'  => $item['product_id'],
                    'site_id'     => $item['site_id'] ?? null,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'discount'    => $item['discount'] ?? 0,
                    'subtotal'    => 0,
                ]);
            }

            $inv->recalculate();

            return $inv;
        });

        return response()->json($invoice->load(['client', 'items.product']), 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(
            $invoice->load(['client', 'items.product', 'items.site', 'creator', 'validator', 'encaissements'])
        );
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'client_id'       => 'sometimes|required|exists:clients,id',
            'type'            => 'sometimes|required|in:pro_forma,definitive,redevance',
            'issue_date'      => 'sometimes|required|date',
            'due_date'        => 'nullable|date|after_or_equal:issue_date',
            'notes'           => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $invoice->update($validated);

        if ($request->has('items')) {
            $itemValidated = $request->validate([
                'items'               => 'required|array|min:1',
                'items.*.product_id'  => 'required|exists:products,id',
                'items.*.site_id'     => 'nullable|exists:sites,id',
                'items.*.description' => 'required|string|max:500',
                'items.*.quantity'    => 'required|numeric|min:0.01',
                'items.*.unit_price'  => 'required|numeric|min:0',
                'items.*.tax_rate'    => 'nullable|numeric|min:0|max:100',
                'items.*.discount'    => 'nullable|numeric|min:0|max:100',
            ]);

            $invoice->items()->delete();
            foreach ($itemValidated['items'] as $item) {
                $invoice->items()->create([
                    'product_id'  => $item['product_id'],
                    'site_id'     => $item['site_id'] ?? null,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'discount'    => $item['discount'] ?? 0,
                ]);
            }

            $invoice->recalculate();
        }

        return response()->json(['message' => 'Facture mise à jour.', 'invoice' => $invoice->fresh()->load(['client', 'items.product', 'items.site', 'creator', 'validator', 'encaissements'])]);
    }

    public function validateInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'brouillon') {
            return response()->json(['message' => 'Cette facture ne peut plus être validée.'], 422);
        }

        $invoice->validate($request->user());

        // Lors de la validation, convertir automatiquement pro_forma en definitive
        if ($invoice->type === 'pro_forma') {
            $invoice->update(['type' => 'definitive']);
        }

        // Promotion client
        if ($invoice->client && $invoice->client->isProspect()) {
            $invoice->client->promoteToClient();
        }

        return response()->json(['message' => 'Facture validée.', 'invoice' => $invoice->fresh()]);
    }

    public function unlockRequest(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate(['reason' => 'required|string|min:10']);

        $invoice->unlockRequests()->create([
            'requested_by' => $request->user()->id,
            'reason'       => $request->reason,
            'status'       => 'pending',
        ]);

        return response()->json(['message' => 'Demande de déverrouillage envoyée.']);
    }

    public function approveUnlock(Request $request, Invoice $invoice): JsonResponse
    {
        $invoice->unlock();

        $invoice->unlockRequests()
            ->where('status', 'pending')
            ->latest()
            ->first()
            ?->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);

        return response()->json(['message' => 'Facture déverrouillée.']);
    }

    public function createSubscription(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $invoice->isDefinitive()) {
            return response()->json(['message' => 'La facture doit être de type définitive.'], 422);
        }

        $validated = $request->validate([
            'start_date'     => 'required|date',
            'monthly_amount' => 'required|numeric|min:0',
        ]);

        $startDate = \Carbon\Carbon::parse($validated['start_date']);

        $sub = Subscription::create([
            'client_id'           => $invoice->client_id,
            'invoice_id'          => $invoice->id,
            'start_date'          => $startDate,
            'current_cycle_start' => $startDate,
            'current_cycle_end'   => $startDate->copy()->addMonth()->subDay(),
            'monthly_amount'      => $validated['monthly_amount'],
            'status'              => 'active',
        ]);

        return response()->json($sub->load('client'), 201);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->isLocked()) {
            return response()->json(['message' => 'Cette facture est verrouillée.'], 403);
        }

        $invoice->update(['status' => 'annulee']);

        return response()->json(['message' => 'Facture annulée.']);
    }
}
