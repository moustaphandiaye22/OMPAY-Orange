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
        $this->twilio = new Client(env('Account_SID'), env('AUTH_TOKEN'));
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
        try {
            $this->twilio->messages->create(
                $to,
                [
                    
                    'from' => env('TWILIO_NUMBER'),
                    'body' => $message
                ]
            );

            return true;
        } catch (\Exception $e) {
            // Log l'erreur
            Log::error('Erreur envoi SMS Twilio: ' . $e->getMessage());
            return false;
        }
    }
}