<?php

namespace Database\Seeders;

class PhoneNumberHelper
{
    public static function formatNumber(string $number): string
    {
        // Si le numéro commence déjà par +221, le retourner tel quel
        if (str_starts_with($number, '+221')) {
            return $number;
        }

        // Supprimer tous les espaces et caractères spéciaux
        $number = preg_replace('/[^0-9]/', '', $number);

        // Si le numéro commence par 221, ajouter juste le +
        if (str_starts_with($number, '221')) {
            return '+' . $number;
        }

        // Sinon, ajouter le préfixe +221
        return '+221' . $number;
    }
}