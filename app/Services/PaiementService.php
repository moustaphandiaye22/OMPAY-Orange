<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Transaction;
use App\Models\Marchand;
use App\Models\QRCode;
use App\Models\CodePaiement;
use App\Interfaces\PaiementServiceInterface;
use App\Traits\ServiceResponseTrait;
use App\Traits\ValidationTrait;
use App\Traits\DataFormattingTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaiementService implements PaiementServiceInterface
{
    use ServiceResponseTrait, ValidationTrait, DataFormattingTrait;
    /**
     * Scanner un QR Code
     *
     * @param string $donneesQR
     * @return array
     */
    public function scannerQR($donneesQR)
    {
        // Parser les données QR (format: OM_PAY_{idMarchand}_{montant}_{timestamp}_{signature})
        $parts = explode('_', $donneesQR);
        if (count($parts) !== 6 || $parts[0] !== 'OM' || $parts[1] !== 'PAY') {
            return $this->errorResponse('PAYMENT_003', 'QR Code invalide', [], 422);
        }

        $idMarchand = $parts[2];
        $montant = (int) $parts[3];
        $timestamp = $parts[4];

        $marchand = Marchand::where('id', $idMarchand)->orWhere('idMarchand', $idMarchand)->first();
        if (!$marchand) {
            return $this->errorResponse('PAYMENT_001', 'Marchand introuvable', [], 404);
        }

        // Vérifier si le QR n'est pas expiré (5 minutes)
        $qrTime = Carbon::createFromTimestamp($timestamp);
        if (Carbon::now()->diffInMinutes($qrTime) > 5) {
            return $this->errorResponse('PAYMENT_004', 'QR Code expiré', [], 422);
        }

        $idScan = 'scn_' . Str::random(10);

        return $this->successResponse([
            'idScan' => $idScan,
            'marchand' => $this->formatMerchantData($marchand),
            'montant' => $montant,
            'devise' => 'XOF',
            'dateExpiration' => $qrTime->addMinutes(5)->toIso8601String(),
            'valide' => true,
        ], 'QR Code scanné avec succès');
    }

    /**
     * Saisir un Code de Paiement
     *
     * @param string $code
     * @return array
     */
    public function saisirCode($code)
    {
        $codePaiement = CodePaiement::where('code', $code)
                                     ->where('utilise', false)
                                     ->where('date_expiration', '>', Carbon::now())
                                     ->first();

        if (!$codePaiement) {
            return $this->errorResponse('PAYMENT_006', 'Code de paiement invalide', [], 422);
        }

        $marchand = $codePaiement->marchand;
        $idCode = 'cod_' . Str::random(10);

        return $this->successResponse([
            'idCode' => $idCode,
            'marchand' => $this->formatMerchantData($marchand),
            'montant' => $codePaiement->montant,
            'devise' => $codePaiement->devise,
            'dateExpiration' => optional($codePaiement->dateExpiration)?->toIso8601String(),
            'valide' => true,
        ], 'Code validé avec succès');
    }

    /**
     * Initier un Paiement
     *
     * @param mixed $utilisateur
     * @param array $data
     * @return array
     */
    public function initierPaiement($utilisateur, $data)
    {
        $marchandId = $data['idMarchand'] ?? $data['id_marchand'] ?? null;
        $montant = $data['montant'] ?? null;
        $modePaiement = $data['modePaiement'] ?? $data['mode_paiement'] ?? null;

        $marchand = Marchand::find($marchandId);

        if (!$marchand) {
            return $this->errorResponse('PAYMENT_001', 'Marchand introuvable', [], 404);
        }

        $portefeuille = $utilisateur->portefeuille;

        if (!$portefeuille) {
            return $this->errorResponse('WALLET_001', 'Portefeuille introuvable', [], 404);
        }

        if ($montant === null || !$this->hasSufficientBalance($portefeuille, $montant)) {
            return $this->errorResponse('WALLET_001', 'Solde insuffisant', [], 422);
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

        return $this->successResponse([
            'idPaiement' => $paiement->id,
            'statut' => $transaction->statut,
            'marchand' => $this->formatMerchantData($marchand),
            'montant' => $transaction->montant,
            'frais' => 0,
            'montantTotal' => $transaction->montant,
            'dateExpiration' => optional($transaction->date_transaction)?->addMinutes(5)?->toIso8601String(),
        ], 'Veuillez confirmer le paiement avec votre code PIN');
    }

    /**
     * Confirmer un Paiement
     *
     * @param mixed $utilisateur
     * @param string $idPaiement
     * @param string $codePin
     * @return array
     */
    public function confirmerPaiement($utilisateur, $idPaiement, $codePin)
    {
        $paiement = Paiement::where('id', $idPaiement)->first();

        if (!$paiement || !$paiement->transaction || !$paiement->transaction->portefeuille ||
            !$this->userOwnsResource($utilisateur, $paiement->transaction->portefeuille)) {
            return $this->errorResponse('PAYMENT_010', 'Paiement introuvable ou non autorisé', [], 404);
        }

        $transaction = $paiement->transaction;

        if ($this->isExpired($transaction)) {
            return $this->errorResponse('PAYMENT_009', 'Paiement expiré', [], 422);
        }

        if (!$this->validatePin($utilisateur, $codePin)) {
            return $this->errorResponse('USER_006', 'Code PIN incorrect', [], 401);
        }

        try {
            $portefeuille = $transaction->portefeuille;

            // Vérifier que le solde est suffisant (double vérification)
            if (!$this->hasSufficientBalance($portefeuille, $transaction->montant)) {
                return $this->errorResponse('WALLET_001', 'Solde insuffisant', [], 422);
            }

            // Débiter l'utilisateur
            $portefeuille->solde -= $transaction->montant;
            $portefeuille->save();

            // Marquer la transaction comme terminée
            $transaction->statut = 'reussie';
            $transaction->save();

            // Mettre à jour le paiement
            $paiement->details_paiement = $paiement->details_paiement ?? [];
            $paiement->save();

            $reference = $transaction->reference ?? ('OM' . date('YmdHis') . rand(100000, 999999));

            return $this->successResponse([
                'idTransaction' => $transaction->id,
                'statut' => 'termine',
                'marchand' => [
                    'nom' => $paiement->marchand->nom,
                    'numeroTelephone' => $paiement->marchand->numero_telephone ?? $paiement->marchand->numeroTelephone ?? null,
                ],
                'montant' => $transaction->montant,
                'dateTransaction' => optional($transaction->date_transaction)?->toIso8601String(),
                'reference' => $reference,
                'recu' => 'https://cdn.ompay.sn/recus/' . $transaction->id . '.pdf',
            ], 'Paiement effectué avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse('PAYMENT_011', 'Erreur lors de la confirmation du paiement: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Annuler un Paiement
     *
     * @param mixed $utilisateur
     * @param string $idPaiement
     * @return array
     */
    public function annulerPaiement($utilisateur, $idPaiement)
    {
        $paiement = Paiement::where('id', $idPaiement)->first();

        if (!$paiement || !$paiement->transaction || !$paiement->transaction->portefeuille ||
            !$this->userOwnsResource($utilisateur, $paiement->transaction->portefeuille)) {
            return $this->errorResponse('PAYMENT_010', 'Paiement introuvable ou non autorisé', [], 404);
        }

        $transaction = $paiement->transaction;

        if ($this->isExpired($transaction)) {
            return $this->errorResponse('PAYMENT_009', 'Paiement expiré', [], 422);
        }

        // Annuler la transaction si possible
        if ($transaction->peutEtreAnnulee()) {
            $transaction->annuler();
        }

        return $this->successResponse(null, 'Paiement annulé avec succès');
    }
}
