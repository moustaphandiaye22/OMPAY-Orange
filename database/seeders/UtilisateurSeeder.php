<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Utilisateur;
use App\Models\Portefeuille;
use Database\Seeders\PhoneNumberHelper;

class UtilisateurSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Créer des utilisateurs de test
        for ($i = 0; $i < 10; $i++) {
            try {
                $utilisateur = Utilisateur::create([
                    'numero_telephone' => "'" . PhoneNumberHelper::formatNumber('77' . rand(1000000, 9999999)) . "'",
                    'prenom' => $this->getRandomPrenom(),
                    'nom' => $this->getRandomNom(),
                    'email' => 'user' . $i . '@example.com',
                    'code_pin' => bcrypt('1234'),
                    'numero_cni' => (string) rand(100000000000, 999999999999),
                    'statut_kyc' => $this->getRandomKycStatus(),
                    'biometrie_activee' => rand(0, 1),
                    'date_creation' => now(),
                ]);
            } catch (\Exception $e) {
                // Log l'erreur et continuer
                \Log::error('Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
                continue;
            }

            Portefeuille::create([
                'id_utilisateur' => $utilisateur->id,
                'solde' => 0,
                'devise' => 'FCFA',
            ]);

            DB::table('parametres_securites')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'id_utilisateur' => $utilisateur->id,
                'biometrie_active' => rand(0, 1),
                'tentatives_echouees' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Créer l'admin
        $admin = Utilisateur::create([
            'numero_telephone' => PhoneNumberHelper::formatNumber('771234567'),
            'prenom' => 'Admin',
            'nom' => 'Orange Money',
            'email' => 'admin@orangemoney.sn',
            'code_pin' => bcrypt('1234'),
            'numero_cni' => '123456789012',
            'statut_kyc' => 'verifie',
            'biometrie_activee' => true,
            'date_creation' => now(),
        ]);

        Portefeuille::create([
            'id_utilisateur' => $admin->id,
            'solde' => 10000,
            'devise' => 'FCFA',
        ]);

        DB::table('parametres_securites')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'id_utilisateur' => $admin->id,
            'biometrie_active' => true,
            'tentatives_echouees' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getRandomPrenom(): string
    {
        $prenoms = ['Mamadou', 'Fatou', 'Ibrahima', 'Aminata', 'Cheikh', 'Ndeye', 'Ousmane', 'Adama', 'Samba', 'Mariama'];
        return $prenoms[array_rand($prenoms)];
    }

    private function getRandomNom(): string
    {
        $noms = ['Diallo', 'Sow', 'Ndiaye', 'Ba', 'Diagne', 'Gueye', 'Fall', 'Sy', 'Kane', 'Mbaye'];
        return $noms[array_rand($noms)];
    }

    private function getRandomKycStatus(): string
    {
        $statuses = ['non_verifie', 'en_cours', 'verifie', 'rejete'];
        return $statuses[array_rand($statuses)];
    }
}
