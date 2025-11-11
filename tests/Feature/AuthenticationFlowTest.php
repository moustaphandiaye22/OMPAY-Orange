<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test le flux complet d'authentification et vérification du solde
     */
    public function test_authentification_et_verification_solde(): void
    {
        // Exécuter les seeders
        $this->artisan('db:seed');

        // Test d'authentification avec le compte admin
            $response = $this->postJson('/api/auth/connexion', [
                'numeroTelephone' => PhoneNumberHelper::formatNumber('771234567'),
                'codePin' => '1234'
            ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token',
                    'message'
                ]);

        // Récupérer le token
        $token = $response->json('token');

        // Vérifier l'accès au solde
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/portefeuille/solde');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'solde',
                    'devise'
                ])
                ->assertJson([
                    'solde' => 10000,
                    'devise' => 'FCFA'
                ]);
    }
}