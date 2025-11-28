<?php

namespace App\Services;

use App\Interfaces\SmsServiceInterface;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioSmsService implements SmsServiceInterface
{
    protected $twilio;

    public function __construct()
    {
        try {
            $this->twilio = new Client(env('Account_SID'), env('AUTH_TOKEN'));
            \Log::info('Twilio client initialized successfully');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'initialisation du client Twilio: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send SMS using Twilio
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function sendSms(string $to, string $message): bool
    {
        // Log des variables d'environnement pour débogage
        Log::info('Twilio config check', [
            'APP_ENV' => app()->environment(),
            'Account_SID_set' => env('Account_SID') ? 'YES' : 'NO',
            'AUTH_TOKEN_set' => env('AUTH_TOKEN') ? 'YES' : 'NO',
            'TWILIO_NUMBER_set' => env('TWILIO_NUMBER') ? 'YES' : 'NO',
            'TWILIO_NUMBER_value' => env('TWILIO_NUMBER')
        ]);

        // En mode développement ou si Twilio n'est pas configuré, simuler l'envoi SMS
        // TEMPORAIRE: Forcer la simulation en production aussi pour les tests
        if (app()->environment('local') || app()->environment('production') || !env('Account_SID') || !env('AUTH_TOKEN') || !env('TWILIO_NUMBER')) {
            Log::info('SMS simulé (mode développement ou Twilio non configuré)', [
                'to' => $to,
                'message' => $message
            ]);
            return true;
        }

        try {
            Log::info('Tentative envoi SMS Twilio réel', [
                'to' => $to,
                'from' => env('TWILIO_NUMBER'),
                'message_length' => strlen($message)
            ]);

            $result = $this->twilio->messages->create(
                $to,
                [
                    'from' => env('TWILIO_NUMBER'),
                    'body' => $message
                ]
            );

            Log::info('SMS Twilio envoyé avec succès', [
                'sid' => $result->sid,
                'status' => $result->status
            ]);

            return true;
        } catch (\Exception $e) {
            // Log détaillé de l'erreur
            Log::error('Erreur envoi SMS Twilio détaillée', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'to' => $to,
                'from' => env('TWILIO_NUMBER'),
                'message_preview' => substr($message, 0, 50) . '...'
            ]);
            return false;
        }
    }
}