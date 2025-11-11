<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\DatabaseSeeder;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentification_et_solde()
    {
        // Exécuter les seeders
        $this->seed(DatabaseSeeder::class);

        // Tester l'authentification
        $response = $this->postJson('/api/auth/connexion', [
            'numeroTelephone' => '+221771234567', // Numéro de l'admin créé dans le seeder
            'codePin' => '1234'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'message'
        ]);

        // Récupérer le token
        $token = $response->json('token');

        // Vérifier l'accès au solde avec le token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/portefeuille/solde');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'solde',
            'devise'
        ]);

        // Vérifier que le solde est de 10000 FCFA (comme défini dans le seeder)
        $this->assertEquals(10000, $response->json('solde'));
        $this->assertEquals('FCFA', $response->json('devise'));
    }
}