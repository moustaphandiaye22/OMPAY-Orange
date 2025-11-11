<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\PhoneNumberHelper;

class ContactTest extends TestCase
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

    public function test_liste_contacts()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/contact/liste');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'contacts' => [
                        '*' => [
                            'id',
                            'nom',
                            'numero_telephone',
                            'nombre_transactions',
                            'derniere_transaction'
                        ]
                    ]
                ]);
    }

    public function test_ajouter_contact()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/contact/ajouter', [
            'nom' => 'Moussa Diallo',
            'numero_telephone' => PhoneNumberHelper::formatNumber('773456789')
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'contact' => [
                        'id',
                        'nom',
                        'numero_telephone'
                    ]
                ]);
    }
}