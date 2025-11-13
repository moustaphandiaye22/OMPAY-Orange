<?php

namespace App\Traits;

/**
 * Trait for common data formatting operations
 */
trait DataFormattingTrait
{
    /**
     * Format user data for API responses
     *
     * @param mixed $user
     * @param array $additionalFields
     * @return array
     */
    protected function formatUserData($user, array $additionalFields = []): array
    {
        $data = [
            'idUtilisateur' => $user->getKey(),
            'numeroTelephone' => $user->numero_telephone,
            'prenom' => $user->prenom,
            'nom' => $user->nom,
            'email' => $user->email,
            'numeroCNI' => $user->numero_cni ?? null,
            'statutKYC' => $user->statut_kyc ?? null,
            'biometrieActivee' => $user->biometrie_activee ?? false,
            'dateCreation' => optional($user->date_creation)?->toIso8601String(),
            'derniereConnexion' => optional($user->derniere_connexion)?->toIso8601String(),
        ];

        return array_merge($data, $additionalFields);
    }

    /**
     * Format merchant data
     *
     * @param mixed $merchant
     * @return array
     */
    protected function formatMerchantData($merchant): array
    {
        return [
            'idMarchand' => $merchant->idMarchand ?? $merchant->id,
            'nom' => $merchant->nom,
            'logo' => $merchant->logo,
            'numeroTelephone' => $merchant->numero_telephone ?? null,
        ];
    }

    /**
     * Format transaction data
     *
     * @param mixed $transaction
     * @param array $additionalFields
     * @return array
     */
    protected function formatTransactionData($transaction, array $additionalFields = []): array
    {
        $data = [
            'idTransaction' => $transaction->id,
            'reference' => $transaction->reference,
            'montant' => $transaction->montant,
            'frais' => $transaction->frais ?? 0,
            'montantTotal' => $transaction->montant + ($transaction->frais ?? 0),
            'devise' => $transaction->devise,
            'statut' => $transaction->statut,
            'dateTransaction' => optional($transaction->date_transaction)?->toIso8601String(),
        ];

        return array_merge($data, $additionalFields);
    }

    /**
     * Format wallet data
     *
     * @param mixed $wallet
     * @return array
     */
    protected function formatWalletData($wallet): array
    {
        return [
            'id' => $wallet->id,
            'solde' => $wallet->solde,
            'devise' => $wallet->devise,
        ];
    }
}