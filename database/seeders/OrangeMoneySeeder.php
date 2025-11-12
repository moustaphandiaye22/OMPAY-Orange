<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\PhoneNumberHelper;

class OrangeMoneySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des comptes Orange Money de test
        $comptes = [
            [
                'numero_telephone' => PhoneNumberHelper::formatNumber('771234567'),
                'prenom' => 'Mamadou',
                'nom' => 'Diallo',
                'email' => 'mamadou.diallo@email.com',
                'numero_cni' => '123456789012',
                'solde' => 50000,
                'date_creation_compte' => now()->subMonths(6),
            ],
            [
                'numero_telephone' => PhoneNumberHelper::formatNumber('762345678'),
                'prenom' => 'Fatou',
                'nom' => 'Sow',
                'email' => 'fatou.sow@email.com',
                'numero_cni' => '234567890123',
                'solde' => 25000,
                'date_creation_compte' => now()->subMonths(4),
            ],
            [
                'numero_telephone' => PhoneNumberHelper::formatNumber('701234567'),
                'prenom' => 'Cheikh',
                'nom' => 'Ndiaye',
                'email' => 'cheikh.ndiaye@email.com',
                'numero_cni' => '345678901234',
                'solde' => 75000,
                'date_creation_compte' => now()->subMonths(8),
            ],
            [
                'numero_telephone' => PhoneNumberHelper::formatNumber('783456789'),
                'prenom' => 'Aminata',
                'nom' => 'Ba',
                'email' => 'aminata.ba@email.com',
                'numero_cni' => '456789012345',
                'solde' => 15000,
                'date_creation_compte' => now()->subMonths(2),
            ],
            [
                'numero_telephone' => PhoneNumberHelper::formatNumber('771411251'),
                'prenom' => 'Moustapha',
                'nom' => 'Ndiaye',
                'email' => 'moustapha.ndiaye@email.com',
                'numero_cni' => '771411251123',
                'solde' => 100000,
                'date_creation_compte' => now()->subMonths(1),
            ],
            [
                'numero_telephone' => PhoneNumberHelper::formatNumber('779999999'),
                'prenom' => 'Test',
                'nom' => 'User',
                'email' => 'test.user@email.com',
                'numero_cni' => '779999999123',
                'solde' => 50000,
                'date_creation_compte' => now()->subDays(1),
            ],
        ];

        foreach ($comptes as $compte) {
            try {
                \App\Models\OrangeMoney::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'numero_telephone' => PhoneNumberHelper::formatNumber($compte['numero_telephone']),
                    'prenom' => $compte['prenom'],
                    'nom' => $compte['nom'],
                    'email' => $compte['email'],
                    'numero_cni' => $compte['numero_cni'],
                    'solde' => $compte['solde'],
                    'date_creation_compte' => $compte['date_creation_compte'],
                    'statut_compte' => 'actif',
                    'devise' => 'FCFA',
                    'derniere_connexion' => rand(0, 1) ? now()->subDays(rand(1, 30)) : null,
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais continue avec les autres comptes
                \Illuminate\Support\Facades\Log::error('Erreur lors de la création du compte : ' . $e->getMessage());
            }
        }

        // Créer des comptes supplémentaires
        for ($i = 0; $i < 25; $i++) {
            $prenoms = ['Mamadou', 'Fatou', 'Ibrahima', 'Aminata', 'Cheikh', 'Ndeye', 'Ousmane', 'Adama', 'Samba', 'Mariama'];
            $noms = ['Diallo', 'Sow', 'Ndiaye', 'Ba', 'Diagne', 'Gueye', 'Fall', 'Sy', 'Kane', 'Mbaye'];

            try {
                \App\Models\OrangeMoney::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'numero_telephone' => PhoneNumberHelper::formatNumber('77' . rand(1000000, 9999999)),
                    'prenom' => $prenoms[array_rand($prenoms)],
                    'nom' => $noms[array_rand($noms)],
                    'email' => rand(0, 1) ? 'user' . ($i + 10) . '@example.com' : null,
                    'numero_cni' => rand(0, 1) ? (string) rand(100000000000, 999999999999) : null,
                    'statut_compte' => rand(0, 10) > 8 ? 'suspendu' : 'actif', // 20% suspendus
                    'solde' => rand(0, 100000),
                    'devise' => 'FCFA',
                    'date_creation_compte' => now()->subDays(rand(1, 365)),
                    'derniere_connexion' => rand(0, 1) ? now()->subDays(rand(1, 30)) : null,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Erreur lors de la création du compte aléatoire : ' . $e->getMessage());
            }
        }
    }
}
