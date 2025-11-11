<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Transaction;
use App\Models\Marchand;
use App\Models\QRCode;
use App\Models\CodePaiement;
use App\Interfaces\PaiementServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaiementService implements PaiementServiceInterface
{
    // 4.2 Scanner un QR Code
    public function scannerQR($donneesQR)
    {
        // Parser les données QR (format: OM_PAY_{idMarchand}_{montant}_{timestamp}_{signature})
        $parts = explode('_', $donneesQR);
        if (count($parts) !== 6 || $parts[0] !== 'OM' || $parts[1] !== 'PAY') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_003',
                    'message' => 'QR Code invalide'
                ],
                'status' => 422
            ];
        }

        $idMarchand = $parts[2];
        $montant = (int) $parts[3];
        $timestamp = $parts[4];
        $signature = $parts[5];

        $marchand = Marchand::where('id', $idMarchand)->orWhere('idMarchand', $idMarchand)->first();
        if (!$marchand) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_001',
                    'message' => 'Marchand introuvable'
                ],
                'status' => 404
            ];
        }

        // Vérifier si le QR n'est pas expiré (5 minutes)
        $qrTime = Carbon::createFromTimestamp($timestamp);
        if (Carbon::now()->diffInMinutes($qrTime) > 5) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_004',
                    'message' => 'QR Code expiré'
                ],
                'status' => 422
            ];
        }

        $idScan = 'scn_' . Str::random(10);

        return [
            'success' => true,
            'data' => [
                'idScan' => $idScan,
                'marchand' => [
                    'idMarchand' => $marchand->idMarchand,
                    'nom' => $marchand->nom,
                    'logo' => $marchand->logo,
                ],
                'montant' => $montant,
                'devise' => 'XOF',
                'dateExpiration' => $qrTime->addMinutes(5)->toIso8601String(),
                'valide' => true,
            ],
            'message' => 'QR Code scanné avec succès'
        ];
    }

    // 4.3 Saisir un Code de Paiement
    public function saisirCode($code)
    {
        $codePaiement = CodePaiement::where('code', $code)
                                     ->where('utilise', false)
                                     ->where('date_expiration', '>', Carbon::now())
                                     ->first();

        if (!$codePaiement) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_006',
                    'message' => 'Code de paiement invalide'
                ],
                'status' => 422
            ];
        }

        $marchand = $codePaiement->marchand;
        $idCode = 'cod_' . Str::random(10);

        return [
            'success' => true,
            'data' => [
                'idCode' => $idCode,
                'marchand' => [
                    'idMarchand' => $marchand->idMarchand,
                    'nom' => $marchand->nom,
                    'logo' => $marchand->logo,
                ],
                'montant' => $codePaiement->montant,
                'devise' => $codePaiement->devise,
                'dateExpiration' => optional($codePaiement->dateExpiration)?->toIso8601String(),
                'valide' => true,
            ],
            'message' => 'Code validé avec succès'
        ];
    }

    // 4.4 Initier un Paiement
    public function initierPaiement($utilisateur, $data)
    {
        $marchandId = $data['idMarchand'] ?? $data['id_marchand'] ?? null;
        $montant = $data['montant'] ?? null;
        $modePaiement = $data['modePaiement'] ?? $data['mode_paiement'] ?? null;

        $marchand = Marchand::find($marchandId);

        if (!$marchand) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_001',
                    'message' => 'Marchand introuvable'
                ],
                'status' => 404
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

        if ($montant === null || $portefeuille->solde < $montant) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Solde insuffisant'
                ],
                'status' => 422
            ];
        }

        // Créer d'abord une transaction liée au portefeuille
        $transaction = new Transaction();
        $transaction->id = (string) Str::uuid();
        $transaction->id_portefeuille = $portefeuille->id;
        $transaction->type = 'paiement';
        $transaction->montant = $montant;
        $transaction->devise = 'XOF';
        $transaction->statut = 'en_attente';
        $transaction->reference = 'OM' . date('YmdHis') . rand(100000, 999999);
        $transaction->date_transaction = Carbon::now();
        $transaction->save();

        // Créer l'enregistrement dans la table paiements
        $paiement = new Paiement();
        $paiement->id = (string) Str::uuid();
        $paiement->id_transaction = $transaction->id;
        $paiement->id_marchand = $marchand->id;
        $paiement->mode_paiement = $modePaiement ?? 'qr_code';
        $paiement->details_paiement = null;
        $paiement->save();

        return [
            'success' => true,
            'data' => [
                'idPaiement' => $paiement->id,
                'statut' => $transaction->statut,
                'marchand' => [
                    'idMarchand' => $marchand->id_marchand ?? $marchand->id,
                    'nom' => $marchand->nom,
                    'logo' => $marchand->logo,
                ],
                'montant' => $transaction->montant,
                'frais' => 0,
                'montantTotal' => $transaction->montant,
                'dateExpiration' => optional($transaction->date_transaction)?->addMinutes(5)?->toIso8601String(),
            ],
            'message' => 'Veuillez confirmer le paiement avec votre code PIN'
        ];
    }

    // 4.5 Confirmer un Paiement
    public function confirmerPaiement($utilisateur, $idPaiement, $codePin)
    {
        $paiement = Paiement::where('id', $idPaiement)->first();

        if (!$paiement || !$paiement->transaction || !$paiement->transaction->portefeuille || ($paiement->transaction->portefeuille->id_utilisateur ?? null) !== ($utilisateur->id ?? null)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_010',
                    'message' => 'Paiement introuvable ou non autorisé'
                ],
                'status' => 404
            ];
        }

        $transaction = $paiement->transaction;

        if (Carbon::now()->isAfter(optional($transaction->date_transaction)?->addMinutes(5) ?? now())) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_009',
                    'message' => 'Paiement expiré'
                ],
                'status' => 422
            ];
        }

        if (!Hash::check($codePin, $utilisateur->code_pin ?? $utilisateur->codePin ?? '')) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_006',
                    'message' => 'Code PIN incorrect'
                ],
                'status' => 401
            ];
        }

        try {
            $portefeuille = $transaction->portefeuille;
            $marchand = $paiement->marchand;

            // Vérifier que le solde est suffisant (double vérification)
            if ($portefeuille->solde < $transaction->montant) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'WALLET_001',
                        'message' => 'Solde insuffisant'
                    ],
                    'status' => 422
                ];
            }

            // Débiter l'utilisateur
            $newBalance = $portefeuille->solde - $transaction->montant;
            $portefeuille->solde = $newBalance;
            $portefeuille->save();

            // Marquer la transaction comme terminée
            $transaction->statut = 'reussie';
            $transaction->save();

            // Mettre à jour le paiement
            $paiement->details_paiement = $paiement->details_paiement ?? [];
            $paiement->save();

            $result = [
                'idTransaction' => $transaction->id,
                'reference' => $transaction->reference ?? ('OM' . date('YmdHis') . rand(100000, 999999)),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_011',
                    'message' => 'Erreur lors de la confirmation du paiement: ' . $e->getMessage()
                ],
                'status' => 500
            ];
        }

        return [
            'success' => true,
            'data' => [
                'idTransaction' => $result['idTransaction'],
                'statut' => 'termine',
                'marchand' => [
                    'nom' => $paiement->marchand->nom,
                    'numeroTelephone' => $paiement->marchand->numero_telephone ?? $paiement->marchand->numeroTelephone ?? null,
                ],
                'montant' => $transaction->montant ?? null,
                'dateTransaction' => optional($transaction->date_transaction)?->toIso8601String(),
                'reference' => $result['reference'],
                'recu' => 'https://cdn.ompay.sn/recus/' . $result['idTransaction'] . '.pdf',
            ],
            'message' => 'Paiement effectué avec succès'
        ];
    }

        // 4.6 Annuler un Paiement
    public function annulerPaiement($utilisateur, $idPaiement)
    {
        $paiement = Paiement::where('id', $idPaiement)->first();

        if (!$paiement || !$paiement->transaction || !$paiement->transaction->portefeuille || ($paiement->transaction->portefeuille->id_utilisateur ?? null) !== ($utilisateur->id ?? null)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_010',
                    'message' => 'Paiement introuvable ou non autorisé'
                ],
                'status' => 404
            ];
        }

        $transaction = $paiement->transaction;

        if (Carbon::now()->isAfter(optional($transaction->date_transaction)?->addMinutes(5) ?? now())) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_009',
                    'message' => 'Paiement expiré'
                ],
                'status' => 422
            ];
        }

        // Annuler la transaction si possible
        if ($transaction->peutEtreAnnulee()) {
            $transaction->annuler();
        }

        return [
            'success' => true,
            'message' => 'Paiement annulé avec succès'
        ];
    }
}
