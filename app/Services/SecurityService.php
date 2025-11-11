<?php

namespace App\Services;

use App\Traits\ServiceResponseTrait;
use App\Traits\ValidationTrait;
use Illuminate\Support\Facades\Hash;

class SecurityService
{
    use ServiceResponseTrait, ValidationTrait;

    /**
     * Changer le code PIN de l'utilisateur
     *
     * @param mixed $utilisateur
     * @param string $ancienPin
     * @param string $nouveauPin
     * @return array
     */
    public function changerPin($utilisateur, $ancienPin, $nouveauPin)
    {
        if (!$this->validatePin($utilisateur, $ancienPin)) {
            return $this->errorResponse('USER_006', 'Ancien PIN incorrect', [], 401);
        }

        if (Hash::check($nouveauPin, $utilisateur->code_pin)) {
            return $this->errorResponse('USER_007', 'Nouveau PIN identique à l\'ancien', [], 422);
        }

        $utilisateur->update(['code_pin' => Hash::make($nouveauPin)]);

        return $this->successResponse(null, 'Code PIN modifié avec succès');
    }

    /**
     * Vérifier le PIN de l'utilisateur
     *
     * @param mixed $utilisateur
     * @param string $codePin
     * @return bool
     */
    public function verifierPin($utilisateur, $codePin)
    {
        return $this->validatePin($utilisateur, $codePin);
    }
}