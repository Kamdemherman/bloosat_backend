<?php
// ═══════════════════════════════════════════════════════════════
// FILE: database/seeders/DatabaseSeeder.php
// ═══════════════════════════════════════════════════════════════
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Role, User, Product, Warehouse, Client};
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ──────────────────────────────────────────────
        $roles = [
            [
                'name'         => 'super_admin',
                'display_name' => 'Super Administrateur',
                'permissions'  => ['*'], // All permissions
            ],
            [
                'name'         => 'dg',
                'display_name' => 'Directeur Général',
                'permissions'  => ['view_logs', 'approve_unlock', 'view_all'],
            ],
            [
                'name'         => 'crd',
                'display_name' => 'Responsable CRD',
                'permissions'  => ['view_logs', 'manage_stock', 'manage_clients'],
            ],
            [
                'name'         => 'comptabilite',
                'display_name' => 'Service Comptabilité',
                'permissions'  => ['validate_invoices', 'manage_encaissements', 'create_subscriptions', 'approve_unlock'],
            ],
            [
                'name'         => 'commercial',
                'display_name' => 'Commercial',
                'permissions'  => ['manage_clients', 'create_invoices', 'view_invoices'],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }

        $superAdminRole  = Role::where('name', 'super_admin')->first();
        $commercialRole  = Role::where('name', 'commercial')->first();
        $comptaRole      = Role::where('name', 'comptabilite')->first();
        $crdRole         = Role::where('name', 'crd')->first();

        // ── Default Users ──────────────────────────────────────
        // Note: spec says SHA1 password hashing. In production use bcrypt minimum.
        // Using sha1 as per spec requirement (not recommended, but spec-compliant)
        $users = [
            ['name' => 'Super Admin',   'email' => 'admin@bloosat.com',   'role_id' => $superAdminRole->id, 'password' => sha1('Admin@2024!')],
            ['name' => 'Jean Dupont',   'email' => 'jean@bloosat.com',     'role_id' => $commercialRole->id, 'password' => sha1('Commercial@1')],
            ['name' => 'Marie Finance', 'email' => 'compta@bloosat.com',   'role_id' => $comptaRole->id,     'password' => sha1('Compta@2024!')],
            ['name' => 'Paul CRD',      'email' => 'crd@bloosat.com',      'role_id' => $crdRole->id,        'password' => sha1('Crd@2024!')],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }

        // ── Warehouses ─────────────────────────────────────────
        $warehouses = [
            ['name' => 'Entrepôt Principal Douala', 'location' => 'Douala, Cameroun'],
            ['name' => 'Entrepôt Yaoundé',          'location' => 'Yaoundé, Cameroun'],
            ['name' => 'Dépôt Bafoussam',           'location' => 'Bafoussam, Cameroun'],
        ];

        foreach ($warehouses as $w) {
            Warehouse::updateOrCreate(['name' => $w['name']], $w);
        }

        // ── Products & Services ────────────────────────────────
        $products = [
            // Equipment (appear on pro-forma + definitive, NOT on redevances)
            ['name' => 'Antenne VSAT 1.2m',        'category' => 'produit', 'type' => null,              'price' => 350000, 'tax_rate' => 19.25],
            ['name' => 'Modem iDirect Evolution',   'category' => 'produit', 'type' => null,              'price' => 280000, 'tax_rate' => 19.25],
            ['name' => 'Câblage + Installation',    'category' => 'produit', 'type' => null,              'price' => 75000,  'tax_rate' => 19.25],
            ['name' => 'BUC 4W Ku-Band',            'category' => 'produit', 'type' => null,              'price' => 195000, 'tax_rate' => 19.25],

            // Non-renewable services (one-time, appear only on first invoices)
            ['name' => 'Frais d\'activation',       'category' => 'service', 'type' => 'non_renouvelable', 'price' => 50000, 'tax_rate' => 19.25],
            ['name' => 'Frais d\'installation',     'category' => 'service', 'type' => 'non_renouvelable', 'price' => 120000,'tax_rate' => 19.25],
            ['name' => 'Formation utilisateur',     'category' => 'service', 'type' => 'non_renouvelable', 'price' => 35000, 'tax_rate' => 19.25],

            // Renewable services (appear on monthly redevances)
            ['name' => 'Forfait Home 10 Go',        'category' => 'service', 'type' => 'renouvelable',     'price' => 45000, 'tax_rate' => 19.25],
            ['name' => 'Forfait Business 50 Go',    'category' => 'service', 'type' => 'renouvelable',     'price' => 120000,'tax_rate' => 19.25],
            ['name' => 'Forfait Entreprise 200 Go', 'category' => 'service', 'type' => 'renouvelable',     'price' => 350000,'tax_rate' => 19.25],
            ['name' => 'IP Publique dédiée',        'category' => 'service', 'type' => 'renouvelable',     'price' => 25000, 'tax_rate' => 19.25],
            ['name' => 'Forfait Illimité Pro',      'category' => 'service', 'type' => 'renouvelable',     'price' => 550000,'tax_rate' => 19.25],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['name' => $p['name']], array_merge($p, ['is_active' => true]));
        }

        // ── Sample Clients ─────────────────────────────────────
        $commercial = User::where('email', 'jean@bloosat.com')->first();

        $clients = [
            [
                'nom'              => 'Dupont',
                'prenom'           => 'Pierre',
                'nature'           => 'physique',
                'type'             => 'ordinaire',
                'status'           => 'prospect',
                'email'            => 'pierre.dupont@example.com',
                'telephone'        => '+237 677 000 001',
                'ville'            => 'Douala',
                'commercial_id'    => $commercial->id,
                'commercial_email' => $commercial->email,
            ],
            [
                'nom'            => 'ENEO Cameroun',
                'raison_sociale' => 'ENEO Cameroun SA',
                'nature'         => 'morale',
                'type'           => 'grand_compte',
                'status'         => 'client',
                'email'          => 'dsi@eneo.cm',
                'telephone'      => '+237 699 000 100',
                'ville'          => 'Yaoundé',
                'ninea'          => 'M123456789X',
                'commercial_id'  => $commercial->id,
                'commercial_email' => $commercial->email,
            ],
            [
                'nom'            => 'Express Union',
                'raison_sociale' => 'Express Union Finance SA',
                'nature'         => 'morale',
                'type'           => 'grand_compte',
                'status'         => 'client',
                'email'          => 'it@expressunion.cm',
                'telephone'      => '+237 698 000 200',
                'ville'          => 'Douala',
                'commercial_id'  => $commercial->id,
                'commercial_email' => $commercial->email,
            ],
        ];

        foreach ($clients as $c) {
            Client::updateOrCreate(['email' => $c['email']], $c);
        }

        $this->command->info('✅ BSS database seeded successfully!');
        $this->command->table(
            ['Module', 'Données créées'],
            [
                ['Rôles',      count($roles)    . ' rôles'],
                ['Utilisateurs', count($users) . ' comptes'],
                ['Entrepôts',  count($warehouses) . ' entrepôts'],
                ['Produits',   count($products) . ' produits/services'],
                ['Clients',    count($clients)  . ' clients/prospects'],
            ]
        );
    }
}
