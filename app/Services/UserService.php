<?php

namespace App\Services;

use App\Models\Utilisateur;
use App\Traits\ServiceResponseTrait;
use App\Traits\DataFormattingTrait;

class UserService
{
    use ServiceResponseTrait, DataFormattingTrait;

    /**
     * Consulter le profil utilisateur
     *
     * @param Utilisateur $utilisateur
     * @return array
     */
    public function consulterProfil($utilisateur)
    {
        return $this->successResponse(
            $this->formatUserData($utilisateur)
        );
    }
}