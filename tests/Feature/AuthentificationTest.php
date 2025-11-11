<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\PhoneNumberHelper;

class AuthentificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Faire une migration fraîche et seed pour éviter les problèmes de transaction en PostgreSQL
        $this->artisan('migrate:fresh --seed');
    }

    public function test_authentification_avec_solde(): void
    {

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