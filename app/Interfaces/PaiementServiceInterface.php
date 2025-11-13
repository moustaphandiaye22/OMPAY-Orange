<?php

namespace App\Interfaces;

interface PaiementServiceInterface
{
    public function scannerQR($donneesQR);
    public function saisirCode($code);
    public function effectuerPaiement($utilisateur, $data);
}