<?php

namespace App\Interfaces;

interface SmsServiceInterface
{
    /**
     * Send SMS to a phone number
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function sendSms(string $to, string $message): bool;
}