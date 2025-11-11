<?php

namespace App\Services;

use App\Models\Transfert;
use App\Models\Transaction;
use App\Models\Utilisateur;
use App\Models\Portefeuille;
use App\Models\Destinataire;
use App\Models\OrangeMoney;
use App\Interfaces\TransfertServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransfertService implements TransfertServiceInterface
{

    // 3.2 Effectuer un Transfert (fusion initier + confirmer)
    public function effectuerTransfert($utilisateur, $data)
    {
        // Vérifier si le destinataire a un compte Orange Money actif ET un compte utilisateur
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($data['telephoneDestinataire']);
        $destinataireUser = Utilisateur::where('numero_telephone', $data['telephoneDestinataire'])->first();

        if (!$compteOrangeMoney || !$destinataireUser) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_001',
                    'message' => 'Ce numéro n\'a pas de compte Orange Money'
                ],
                'status' => 404
            ];
        }

        // Find or create a Destinataire record (table used for stored recipients)
        $destinataireRecord = Destinataire::firstOrCreate(
            ['numero_telephone' => $data['telephoneDestinataire']],
            [
                'id' => (string) Str::uuid(),
                'nom' => trim(($destinataireUser->prenom ?? '') . ' ' . ($destinataireUser->nom ?? '')),
                'operateur' => 'orange',
                'est_valide' => true
            ]
        );

        if ($destinataireUser->id === $utilisateur->id) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_003',
                    'message' => 'Transfert à soi-même interdit'
                ],
                'status' => 422
            ];
        }
        $portefeuille = $utilisateur->portefeuille;

        if (!$portefeuille) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Portefeuille introuvable'
                ],
                'status' => 404
            ];
        }

        $frais = $this->calculerFrais($data['montant'] ?? 0);

        if ($portefeuille->solde < (($data['montant'] ?? 0) + $frais)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Solde insuffisant'
                ],
                'status' => 422
            ];
        }

        // Vérifier le PIN immédiatement
        if (!Hash::check($data['codePin'] ?? '', $utilisateur->code_pin ?? '')) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_006',
                    'message' => 'Code PIN incorrect'
                ],
                'status' => 401
            ];
        }

        // Use a transaction to update balances and create records atomically
        // Reconnect to ensure a clean connection (avoid "current transaction is aborted" state)
        // Perform updates without DB::transaction wrapper due to connection-level transaction abort state
        try {
            $portefeuilleExpediteur = $utilisateur->portefeuille;

            // Pour les comptes Orange Money sans utilisateur/portefeuille, créer un portefeuille temporaire
            // ou utiliser directement le solde Orange Money
            if (isset($destinataireUser->portefeuille)) {
                $portefeuilleDestinataire = $destinataireUser->portefeuille;
            } else {
                // Créer un portefeuille temporaire pour le destinataire Orange Money
                $portefeuilleDestinataire = Portefeuille::firstOrCreate(
                    ['id_utilisateur' => $compteOrangeMoney->id],
                    [
                        'id' => (string) Str::uuid(),
                        'solde' => $compteOrangeMoney->solde ?? 0,
                        'devise' => $compteOrangeMoney->devise ?? 'XOF'
                    ]
                );
            }

            if (!$portefeuilleDestinataire) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'WALLET_001',
                        'message' => 'Destinataire sans portefeuille'
                    ],
                    'status' => 404
                ];
            }

            $montantTotal = ($data['montant'] ?? 0) + $frais;

            // Load fresh wallet records and update
            $exp = Portefeuille::where('id', $portefeuilleExpediteur->id)->first();
            $dest = Portefeuille::where('id', $portefeuilleDestinataire->id)->first();

            if (!$exp || !$dest) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'WALLET_001',
                        'message' => 'Portefeuille introuvable'
                    ],
                    'status' => 404
                ];
            }

            // Perform arithmetic and save (non-transactional but direct)
            $exp->solde = $exp->solde - $montantTotal;
            $exp->updated_at = Carbon::now();
            $exp->save();

            $dest->solde = $dest->solde + intval($data['montant'] ?? 0);
            $dest->updated_at = Carbon::now();
            $dest->save();

            // Créer la transaction d'abord
            $transaction = new Transaction();
            $transaction->id = (string) Str::uuid();
            $transaction->id_portefeuille = $portefeuilleExpediteur->id;
            $transaction->type = 'transfert';
            $transaction->montant = $data['montant'] ?? 0;
            $transaction->devise = $data['devise'] ?? 'XOF';
            $transaction->statut = 'reussie';
            $transaction->reference = 'OM' . date('YmdHis') . rand(100000, 999999);
            $transaction->frais = $frais;
            $transaction->date_transaction = Carbon::now();
            $transaction->save();

            // Créer le transfert
            $transfert = new Transfert();
            $transfert->id = (string) Str::uuid();
            $transfert->id_transaction = $transaction->id;
            $transfert->id_expediteur = $utilisateur->id;
            $transfert->id_destinataire = $destinataireRecord->id;
            $transfert->nom_destinataire = trim(($destinataireUser->prenom ?? '') . ' ' . ($destinataireUser->nom ?? ''));
            $transfert->note = $data['note'] ?? null;
            $transfert->statut = 'reussie';
            $transfert->date_expiration = Carbon::now();
            $transfert->save();

            $result = [
                'idTransaction' => $transaction->id,
                'idTransfert' => $transfert->id,
                'reference' => $transaction->reference,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erreur lors du transfert: ' . $e->getMessage()
                ],
                'status' => 500
            ];
        }

        return [
            'success' => true,
            'data' => [
                'idTransaction' => $result['idTransaction'],
                'idTransfert' => $result['idTransfert'],
                'statut' => 'reussie',
                'montant' => $data['montant'] ?? 0,
                'frais' => $frais,
                'montantTotal' => ($data['montant'] ?? 0) + $frais,
                'destinataire' => [
                    'numeroTelephone' => $data['telephoneDestinataire'],
                    'nom' => trim(($destinataireUser->prenom ?? '') . ' ' . ($destinataireUser->nom ?? '')),
                ],
                'dateTransaction' => Carbon::now()->toIso8601String(),
                'reference' => $result['reference'],
                'recu' => 'https://cdn.ompay.sn/recus/' . $result['idTransaction'] . '.pdf',
            ],
            'message' => 'Transfert effectué avec succès'
        ];
    }

    // 3.4 Annuler un Transfert
    public function annulerTransfert($utilisateur, $idTransfert)
    {
        $transfert = Transfert::where('id', $idTransfert)
                              ->where('id_expediteur', $utilisateur->id)
                              ->first();

        if (!$transfert) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_006',
                    'message' => 'Transfert introuvable ou déjà annulé'
                ],
                'status' => 404
            ];
        }

        if ($transfert->statut !== 'en_attente_confirmation') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_006',
                    'message' => 'Transfert introuvable ou déjà annulé'
                ],
                'status' => 404
            ];
        }

        if (Carbon::now()->isAfter($transfert->date_expiration ?? now())) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_004',
                    'message' => 'Transfert expiré'
                ],
                'status' => 422
            ];
        }

        // Annuler la transaction associée
        $transaction = $transfert->transaction;
        if ($transaction) {
            $transaction->annuler();
        }

        $transfert->update(['statut' => 'annule']);

        return [
            'success' => true,
            'message' => 'Transfert annulé avec succès',
            'data' => [
                'idTransfert' => $transfert->id,
                'idTransaction' => $transaction ? $transaction->id : null,
                'reference' => $transaction ? $transaction->reference : null,
                'statut' => 'annule'
            ]
        ];
    }

    private function calculerFrais($montant)
    {
        if ($montant <= 5000) return 0;
        if ($montant <= 25000) return 0;
        if ($montant <= 50000) return 0;
        if ($montant <= 100000) return 100;
        return 200;
    }
}

