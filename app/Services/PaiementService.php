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

        $marchand = Marchand::where('idMarchand', $idMarchand)->first();
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
     * Effectuer un Paiement (initiation + confirmation en une seule opération)
     *
     * @param mixed $utilisateur
     * @param array $data
     * @return array
     */
    public function effectuerPaiement($utilisateur, $data)
    {
        $montant = $data['montant'] ?? null;
        $codePin = $data['codePin'] ?? null;
        $modePaiement = $data['modePaiement'] ?? $data['mode_paiement'] ?? null;

        $portefeuille = $utilisateur->portefeuille;

        if (!$portefeuille) {
            return $this->errorResponse('WALLET_001', 'Portefeuille introuvable', [], 404);
        }

        if ($montant === null || !$this->hasSufficientBalance($portefeuille, $montant)) {
            return $this->errorResponse('WALLET_001', 'Solde insuffisant', [], 422);
        }

        if (!$this->validatePin($utilisateur, $codePin)) {
            return $this->errorResponse('USER_006', 'Code PIN incorrect', [], 401);
        }

        // Traiter selon le mode de paiement
        $marchand = null;
        $destinataireUtilisateur = null;
        $modeDetails = [];

        switch ($modePaiement) {
            case 'qr_code':
                $donneesQR = $data['donneesQR'] ?? null;
                if (!$donneesQR) {
                    return $this->errorResponse('PAYMENT_003', 'Données QR manquantes', [], 422);
                }

                // Décoder le QR code pour déterminer le type
                $decodedQR = QRCode::decoder($donneesQR);
                if (!$decodedQR) {
                    return $this->errorResponse('PAYMENT_003', 'QR Code invalide', [], 422);
                }

                if ($decodedQR['type'] === 'marchand') {
                    // QR code marchand
                    $qrResult = $this->scannerQR($donneesQR);
                    if (!$qrResult['success']) {
                        return $qrResult;
                    }

                    $marchandId = $qrResult['data']['marchand']['idMarchand'];
                    $marchand = Marchand::where('idMarchand', $marchandId)->first();
                    $modeDetails = ['type' => 'qr_code', 'donneesQR' => $donneesQR];
                } elseif ($decodedQR['type'] === 'utilisateur') {
                    // QR code utilisateur (contient le numéro de téléphone)
                    $numeroTelephone = $decodedQR['numero_telephone'];
                    $destinataireUtilisateur = \App\Models\Utilisateur::where('numero_telephone', $numeroTelephone)->first();

                    if (!$destinataireUtilisateur) {
                        return $this->errorResponse('PAYMENT_003', 'Utilisateur destinataire introuvable', [], 422);
                    }

                    $modeDetails = ['type' => 'qr_code_utilisateur', 'donneesQR' => $donneesQR, 'numeroTelephone' => $numeroTelephone];
                }
                break;

            case 'code':
                $code = $data['code'] ?? null;
                if (!$code) {
                    return $this->errorResponse('PAYMENT_006', 'Code de paiement manquant', [], 422);
                }

                // Valider le code de paiement pour obtenir les informations du marchand
                $codeResult = $this->saisirCode($code);
                if (!$codeResult['success']) {
                    return $codeResult;
                }

                $marchandId = $codeResult['data']['marchand']['idMarchand'];
                $marchand = Marchand::where('idMarchand', $marchandId)->first();
                $modeDetails = ['type' => 'code_numerique', 'code' => $code];
                break;

            case 'telephone':
                $numeroTelephone = $data['numeroTelephone'] ?? null;
                if (!$numeroTelephone) {
                    return $this->errorResponse('PAYMENT_012', 'Numéro de téléphone manquant', [], 422);
                }

                // Normaliser le numéro de téléphone (supprimer +221 si présent)
                $numeroNormalise = ltrim($numeroTelephone, '+221');

                // Vérifier si c'est un paiement vers un utilisateur (comme un transfert)
                $destinataireUtilisateur = \App\Models\Utilisateur::where('numero_telephone', $numeroNormalise)->first();
                if ($destinataireUtilisateur) {
                    // C'est un paiement vers un utilisateur - traiter comme tel
                    $modeDetails = ['type' => 'telephone_utilisateur', 'numeroTelephone' => $numeroTelephone];
                    break;
                }

                // Sinon, chercher un marchand existant
                $marchand = Marchand::where('numero_telephone', $numeroNormalise)->first();
                if (!$marchand) {
                    return $this->errorResponse('PAYMENT_013', 'Destinataire non trouvé ou ne peut pas recevoir de paiements', [], 404);
                }
                $modeDetails = ['type' => 'telephone', 'numeroTelephone' => $numeroTelephone];
                break;

            default:
                return $this->errorResponse('PAYMENT_014', 'Mode de paiement non supporté', [], 422);
        }

        // Vérifier qu'on a soit un marchand soit un destinataire utilisateur
        if (!$marchand && !$destinataireUtilisateur) {
            return $this->errorResponse('PAYMENT_001', 'Destinataire introuvable', [], 404);
        }

        try {
            // Créer d'abord une transaction liée au portefeuille
            $transaction = new Transaction();
            $transaction->id = (string) Str::uuid();
            $transaction->id_portefeuille = $portefeuille->id;
            $transaction->type = 'paiement';
            $transaction->montant = $montant;
            $transaction->devise = 'XOF';
            $transaction->statut = 'reussie'; // Directement marquée comme réussie
            $transaction->reference = 'OM' . date('YmdHis') . rand(100000, 999999);
            $transaction->date_transaction = Carbon::now();
            $transaction->save();

            // Créer l'enregistrement dans la table paiements
            $paiement = new Paiement();
            $paiement->id = (string) Str::uuid();
            $paiement->id_transaction = $transaction->id;

            if ($marchand) {
                $paiement->id_marchand = $marchand->id;
            } elseif ($destinataireUtilisateur) {
                // Pour les paiements utilisateur, on peut soit créer un marchand temporaire
                // soit utiliser un champ spécial. Ici on crée un marchand temporaire
                $marchandTemp = Marchand::firstOrCreate(
                    ['numero_telephone' => $destinataireUtilisateur->numero_telephone],
                    [
                        'id' => (string) Str::uuid(),
                        'idMarchand' => 'USER_' . strtoupper(Str::random(8)),
                        'nom' => $destinataireUtilisateur->prenom . ' ' . $destinataireUtilisateur->nom,
                        'numero_telephone' => $destinataireUtilisateur->numero_telephone,
                        'categorie' => 'Utilisateur',
                        'actif' => true,
                        'accepte_qr' => false,
                        'accepte_code' => false,
                    ]
                );
                $paiement->id_marchand = $marchandTemp->id;
            }

            $paiement->mode_paiement = $modePaiement === 'code' ? 'code_numerique' : $modePaiement;
            $paiement->details_paiement = $modeDetails;
            $paiement->save();

            // Débiter l'utilisateur expéditeur
            $portefeuille->solde -= $transaction->montant;
            $portefeuille->save();

            // Créditer le destinataire si c'est un paiement vers un utilisateur
            if ($destinataireUtilisateur && $destinataireUtilisateur->portefeuille) {
                $destinataireUtilisateur->portefeuille->solde += $transaction->montant;
                $destinataireUtilisateur->portefeuille->save();
            }

            // Préparer la réponse
            $responseData = [
                'idTransaction' => $transaction->id,
                'statut' => 'termine',
                'montant' => $transaction->montant,
                'dateTransaction' => optional($transaction->date_transaction)?->toIso8601String(),
                'reference' => $transaction->reference,
                'recu' => 'https://cdn.ompay.sn/recus/' . $transaction->id . '.pdf',
                'modePaiement' => $modePaiement,
            ];

            if ($marchand) {
                $responseData['marchand'] = [
                    'nom' => $marchand->nom,
                    'numeroTelephone' => $marchand->numero_telephone ?? $marchand->numeroTelephone ?? null,
                ];
            } elseif ($destinataireUtilisateur) {
                $responseData['destinataire'] = [
                    'nom' => $destinataireUtilisateur->prenom . ' ' . $destinataireUtilisateur->nom,
                    'numeroTelephone' => $destinataireUtilisateur->numero_telephone,
                ];
            }

            return $this->successResponse($responseData, 'Paiement effectué avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse('PAYMENT_011', 'Erreur lors du paiement: ' . $e->getMessage(), [], 500);
        }
    }

}
