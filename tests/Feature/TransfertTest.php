<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\PhoneNumberHelper;

class TransfertTest extends TestCase
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

    public function test_verifier_destinataire()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfert/verifier-destinataire', [
            'numero_telephone' => PhoneNumberHelper::formatNumber('772345678')
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'existe',
                    'nom' // si le destinataire existe
                ]);
    }

    public function test_initier_transfert()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfert/initier-transfert', [
            'numero_telephone' => PhoneNumberHelper::formatNumber('772345678'),
            'montant' => 5000
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id_transfert',
                    'frais',
                    'montant_total'
                ]);
    }

    public function test_confirmer_transfert()
    {
        // D'abord initier un transfert
        $initResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfert/initier-transfert', [
            'numero_telephone' => PhoneNumberHelper::formatNumber('772345678'),
            'montant' => 5000
        ]);

        $idTransfert = $initResponse->json('id_transfert');

        // Confirmer le transfert
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson("/api/transfert/{$idTransfert}/confirmer-transfert", [
            'code_pin' => '1234'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'reference_transfert'
                ]);
    }
}