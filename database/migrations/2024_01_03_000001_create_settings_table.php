<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('label')->nullable();
            $table->string('type', 30)->default('text'); // text, email, tel, number, boolean, color, select
            $table->timestamps();
        });

        // Default settings
        $defaults = [
            // Entreprise
            ['key' => 'company_name',    'value' => 'BLOOSAT SA',              'group' => 'entreprise', 'label' => 'Nom de la société',     'type' => 'text'],
            ['key' => 'company_address', 'value' => 'Douala, Cameroun',         'group' => 'entreprise', 'label' => 'Adresse',               'type' => 'text'],
            ['key' => 'company_phone',   'value' => '+237 6XX XXX XXX',         'group' => 'entreprise', 'label' => 'Téléphone',             'type' => 'tel'],
            ['key' => 'company_email',   'value' => 'contact@bloosat.com',      'group' => 'entreprise', 'label' => 'Email',                 'type' => 'email'],
            ['key' => 'company_website', 'value' => 'https://www.bloosat.com',  'group' => 'entreprise', 'label' => 'Site web',              'type' => 'text'],
            ['key' => 'company_logo',    'value' => '',                         'group' => 'entreprise', 'label' => 'Logo',                  'type' => 'file'],
            ['key' => 'company_ninea',   'value' => '',                         'group' => 'entreprise', 'label' => 'NINEA',                 'type' => 'text'],
            ['key' => 'company_rccm',    'value' => '',                         'group' => 'entreprise', 'label' => 'RCCM',                  'type' => 'text'],

            // Facturation
            ['key' => 'default_tva',          'value' => '19.25', 'group' => 'facturation', 'label' => 'Taux TVA par défaut (%)',       'type' => 'number'],
            ['key' => 'invoice_prefix_pf',    'value' => 'PF',    'group' => 'facturation', 'label' => 'Préfixe facture pro-forma',     'type' => 'text'],
            ['key' => 'invoice_prefix_fa',    'value' => 'FA',    'group' => 'facturation', 'label' => 'Préfixe facture définitive',    'type' => 'text'],
            ['key' => 'invoice_prefix_rd',    'value' => 'RD',    'group' => 'facturation', 'label' => 'Préfixe redevance',             'type' => 'text'],
            ['key' => 'invoice_footer_notes', 'value' => 'Merci de votre confiance. Paiement à réception de facture.', 'group' => 'facturation', 'label' => 'Note de bas de facture', 'type' => 'text'],
            ['key' => 'currency',             'value' => 'FCFA',  'group' => 'facturation', 'label' => 'Devise',                       'type' => 'text'],

            // Notifications
            ['key' => 'accounting_email',     'value' => 'comptabilite@bloosat.com', 'group' => 'notifications', 'label' => 'Email comptabilité',            'type' => 'email'],
            ['key' => 'notif_suspension',     'value' => '1',   'group' => 'notifications', 'label' => 'Notifier suspension automatique',   'type' => 'boolean'],
            ['key' => 'notif_relance_7j',     'value' => '1',   'group' => 'notifications', 'label' => 'Relance J-7 (ordinaire)',           'type' => 'boolean'],
            ['key' => 'notif_relance_2j',     'value' => '1',   'group' => 'notifications', 'label' => 'Relance J-2 (ordinaire)',           'type' => 'boolean'],
            ['key' => 'notif_gc_2j',          'value' => '1',   'group' => 'notifications', 'label' => 'Relance J+2 (grand compte)',        'type' => 'boolean'],
            ['key' => 'notif_gc_7j',          'value' => '1',   'group' => 'notifications', 'label' => 'Relance J+7 (grand compte)',        'type' => 'boolean'],
            ['key' => 'notif_gc_15j',         'value' => '1',   'group' => 'notifications', 'label' => 'Relance J+15 (grand compte)',       'type' => 'boolean'],

            // Système
            ['key' => 'timezone',        'value' => 'Africa/Douala', 'group' => 'systeme', 'label' => 'Fuseau horaire', 'type' => 'text'],
            ['key' => 'date_format',     'value' => 'd/m/Y',         'group' => 'systeme', 'label' => 'Format de date', 'type' => 'text'],
            ['key' => 'items_per_page',  'value' => '20',            'group' => 'systeme', 'label' => 'Éléments par page', 'type' => 'number'],
            ['key' => 'kaf_api_url',     'value' => '',              'group' => 'systeme', 'label' => 'URL API KAF (suspension)', 'type' => 'text'],
            ['key' => 'kaf_api_key',     'value' => '',              'group' => 'systeme', 'label' => 'Clé API KAF', 'type' => 'text'],
            ['key' => 'iway_api_url',    'value' => '',              'group' => 'systeme', 'label' => 'URL API Iway', 'type' => 'text'],
            ['key' => 'iway_api_key',    'value' => '',              'group' => 'systeme', 'label' => 'Clé API Iway', 'type' => 'text'],
        ];

        foreach ($defaults as $s) {
            DB::table('settings')->insert(array_merge($s, ['created_at' => now(), 'updated_at' => now()]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
