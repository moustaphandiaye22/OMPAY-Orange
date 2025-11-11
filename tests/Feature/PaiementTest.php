<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\PhoneNumberHelper;

class PaiementTest extends TestCase
{
    use RefreshDatabase;

    private $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Exécuter les seeders
        $this->seed();

        // Obtenir un token
        $response = $this->postJson('/api/auth/connexion', [
            'numeroTelephone' => PhoneNumberHelper::formatNumber('771234567'),
            'codePin' => '1234'
        ]);

        $this->token = $response->json('token');
    }

    public function test_scanner_qr()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/scanner-qr', [
            'qr_code' => 'MERCHANT123' // Format à adapter selon votre implémentation
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'marchand' => [
                        'nom',
                        'numero_marchand'
                    ]
                ]);
    }

    public function test_initier_paiement()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/initier-paiement', [
            'numero_marchand' => 'MERCHANT123',
            'montant' => 1000,
            'reference' => 'REF' . time()
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id_paiement',
                    'frais',
                    'montant_total'
                ]);
    }

    public function test_confirmer_paiement()
    {
        // D'abord initier un paiement
        $initResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/initier-paiement', [
            'numero_marchand' => 'MERCHANT123',
            'montant' => 1000,
            'reference' => 'REF' . time()
        ]);

        $idPaiement = $initResponse->json('id_paiement');

        // Confirmer le paiement
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson("/api/paiement/{$idPaiement}/confirmer-paiement", [
            'code_pin' => '1234'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'reference_paiement'
                ]);
    }
}