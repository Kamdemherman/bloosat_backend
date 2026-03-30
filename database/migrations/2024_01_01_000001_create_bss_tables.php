<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── USERS & ROLES ───────────────────────────────────────────────────
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // super_admin, comptabilite, commercial, crd, dg
            $table->string('display_name');
            $table->json('permissions')->nullable(); // JSON array of allowed actions
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password'); // SHA1 as per spec
            $table->boolean('is_active')->default(true);
            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // ─── CLIENTS ─────────────────────────────────────────────────────────
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_id')->nullable()->constrained('users');
            $table->enum('type', ['ordinaire', 'grand_compte'])->default('ordinaire');
            $table->enum('status', ['prospect', 'client', 'inactif'])->default('prospect');
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('raison_sociale')->nullable();
            $table->enum('nature', ['physique', 'morale'])->default('physique');
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('Cameroun');
            $table->string('ninea')->nullable(); // Numéro fiscal
            $table->string('rccm')->nullable();
            $table->string('commercial_email')->nullable(); // Email du commercial (notifications)
            $table->boolean('is_suspended')->default(false);
            $table->text('notes')->nullable();
            $table->softDeletes(); // Prospects peuvent être supprimés
            $table->timestamps();
        });

        // ─── PRODUCTS & SERVICES ──────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['produit', 'service'])->default('produit');
            $table->enum('type', ['renouvelable', 'non_renouvelable'])->nullable(); // Pour les services
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(19.25); // TVA Cameroun
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // Grilles tarifaires spécifiques par client
        Schema::create('client_price_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('custom_price', 12, 2);
            $table->timestamps();
        });

        // ─── SITES ────────────────────────────────────────────────────────────
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('name');
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─── INVOICES (PRO-FORMA + DEFINITIVE) ───────────────────────────────
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->string('number')->unique();
            $table->enum('type', ['pro_forma', 'definitive', 'redevance'])->default('pro_forma');
            $table->enum('status', ['brouillon', 'validee', 'verrouillee', 'payee', 'annulee'])->default('brouillon');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('site_id')->nullable()->constrained('sites');
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // Invoice unlock requests
        Schema::create('invoice_unlock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // ─── SUBSCRIPTIONS ────────────────────────────────────────────────────
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('invoice_id')->constrained('invoices'); // First definitive invoice
            $table->date('start_date');
            $table->date('current_cycle_start');
            $table->date('current_cycle_end');
            $table->decimal('monthly_amount', 12, 2);
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamps();
        });

        // Monthly redevance invoices
        Schema::create('redevances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['non_payee', 'payee', 'annulee'])->default('non_payee');
            $table->timestamps();
        });

        // ─── ENCAISSEMENTS (PAYMENTS) ─────────────────────────────────────────
        Schema::create('encaissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('redevance_id')->nullable()->constrained('redevances');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('created_by')->constrained('users');
            $table->string('reference')->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['especes', 'virement', 'cheque', 'mobile_money', 'autre']);
            $table->date('payment_date');
            $table->string('proof_path'); // Required attachment
            $table->text('notes')->nullable();
            $table->enum('status', ['valide', 'annule'])->default('valide');
            $table->timestamps();
        });

        // ─── STOCK ────────────────────────────────────────────────────────────
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('min_quantity', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('site_id')->nullable()->constrained('sites'); // For installations
            $table->enum('type', ['entree', 'sortie', 'transfert', 'installation', 'retour']);
            $table->decimal('quantity', 10, 2);
            $table->text('reason');
            $table->date('movement_date');
            $table->timestamps();
        });

        // ─── SYSTEM LOGS ─────────────────────────────────────────────────────
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action'); // created, updated, deleted, restored, validated, etc.
            $table->string('model_type'); // App\Models\Client, App\Models\Invoice, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('encaissements');
        Schema::dropIfExists('redevances');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('invoice_unlock_requests');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('client_price_overrides');
        Schema::dropIfExists('products');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
