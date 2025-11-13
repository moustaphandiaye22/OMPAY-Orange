<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Trait for common validation operations
 */
trait ValidationTrait
{
    /**
     * Validate user PIN
     *
     * @param mixed $user
     * @param string $pin
     * @return bool
     */
    protected function validatePin($user, string $pin): bool
    {
        return Hash::check($pin, $user->code_pin ?? $user->codePin ?? '');
    }

    /**
     * Check if user owns the resource
     *
     * @param mixed $user
     * @param mixed $resource
     * @param string $userIdField
     * @return bool
     */
    protected function userOwnsResource($user, $resource, string $userIdField = 'id_utilisateur'): bool
    {
        return ($resource->{$userIdField} ?? null) === ($user->id ?? null);
    }

    /**
     * Check if resource is expired
     *
     * @param mixed $resource
     * @param string $expirationField
     * @return bool
     */
    protected function isExpired($resource, string $expirationField = 'date_expiration'): bool
    {
        $expirationDate = $resource->{$expirationField} ?? null;
        return $expirationDate && Carbon::now()->isAfter($expirationDate);
    }

    /**
     * Validate sufficient balance
     *
     * @param mixed $wallet
     * @param float $amount
     * @param float $fees
     * @return bool
     */
    protected function hasSufficientBalance($wallet, float $amount, float $fees = 0): bool
    {
        return ($wallet->solde ?? 0) >= ($amount + $fees);
    }

    /**
     * Validate UUID format
     *
     * @param string $id
     * @return bool
     */
    protected function isValidUuid(string $id): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) === 1;
    }
}