<?php

namespace App\Http\Controllers;

use App\Interfaces\PaiementServiceInterface;
use App\Http\Requests\ScannerQRRequest;
use App\Http\Requests\SaisirCodeRequest;
use App\Http\Requests\InitierPaiementRequest;
use App\Http\Requests\ConfirmerPaiementRequest;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Paiements",
 *     description="Endpoints pour gérer les paiements marchands"
 * )
 */
class PaiementController extends Controller
{
    protected $paiementService;

    public function __construct(PaiementServiceInterface $paiementService)
    {
        $this->paiementService = $paiementService;
    }

    /**
     * @OA\Post(
     *     path="/paiement/scanner-qr",
     *     summary="Scanner un code QR",
     *     description="Scanne un code QR et récupère les informations du marchand",
     *     operationId="scannerQR",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"donneesQR"},
     *             @OA\Property(property="donneesQR", type="string", example="OM_PAY_76104421-859e-4710-99d7-2800cefa5b0a_5000.00_1762861353_33f391d122cfedbee4cb4e3fc0a49166", description="Données du code QR au format OM_PAY_{idMarchand}_{montant}_{timestamp}_{signature}")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Données du QR décodées",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idScan", type="string", example="scn_aKm2WAosDn"),
     *                 @OA\Property(property="marchand", type="object",
     *                     @OA\Property(property="idMarchand", type="string", example="MCH_VRMT7VQP"),
     *                     @OA\Property(property="nom", type="string", example="Boutique Orange"),
     *                     @OA\Property(property="logo", type="string", example="https://cdn.ompay.sn/logos/boutique_orange.png")
     *                 ),
     *                 @OA\Property(property="montant", type="number", example=22068),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="dateExpiration", type="string", format="date-time", example="2025-11-11T11:07:01+00:00"),
     *                 @OA\Property(property="valide", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="QR Code scanné avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="QR Code invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_004"),
     *                 @OA\Property(property="message", type="string", example="QR Code expiré")
     *             )
     *         )
     *     )
     * )
     */
    public function scannerQR(ScannerQRRequest $request)
    {
        $result = $this->paiementService->scannerQR($request->donneesQR);
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/paiement/saisir-code",
     *     summary="Saisir un code de paiement",
     *     description="Saisir manuellement un code de paiement",
     *     operationId="saisirCode",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="FRESH001", description="Code de paiement alphanumérique")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code validé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idCode", type="string", example="cod_NlqcI9RK6b"),
     *                 @OA\Property(property="marchand", type="object",
     *                     @OA\Property(property="idMarchand", type="string", example="MCH_VRMT7VQP"),
     *                     @OA\Property(property="nom", type="string", example="Boutique Orange"),
     *                     @OA\Property(property="logo", type="string", example="https://cdn.ompay.sn/logos/boutique_orange.png")
     *                 ),
     *                 @OA\Property(property="montant", type="number", example=2500.00),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="dateExpiration", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="valide", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Code validé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Code invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_006"),
     *                 @OA\Property(property="message", type="string", example="Code de paiement invalide")
     *             )
     *         )
     *     )
     * )
     */
    public function saisirCode(SaisirCodeRequest $request)
    {
        $result = $this->paiementService->saisirCode($request->code);
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/paiement/initier-paiement",
     *     summary="Initier un paiement",
     *     description="Crée et initie une transaction de paiement",
     *     operationId="initierPaiement",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"idMarchand","montant","devise"},
     *             @OA\Property(property="idMarchand", type="string", example="76104421-859e-4710-99d7-2800cefa5b0a", description="ID du marchand"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant du paiement"),
     *             @OA\Property(property="devise", type="string", example="XOF", description="Devise")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement initié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idPaiement", type="string", example="93027583-73d6-401a-b598-64ce2aede79b"),
     *                 @OA\Property(property="statut", type="string", example="en_attente"),
     *                 @OA\Property(property="marchand", type="object",
     *                     @OA\Property(property="idMarchand", type="string", example="76104421-859e-4710-99d7-2800cefa5b0a"),
     *                     @OA\Property(property="nom", type="string", example="Restaurant LE PALACE"),
     *                     @OA\Property(property="logo", type="string", example="https://cdn.ompay.sn/logos/restaurant.png")
     *                 ),
     *                 @OA\Property(property="montant", type="number", example=5000.00),
     *                 @OA\Property(property="frais", type="number", example=0),
     *                 @OA\Property(property="montantTotal", type="number", example=5000.00),
     *                 @OA\Property(property="dateExpiration", type="string", format="date-time", example="2025-11-11T11:15:52+00:00")
     *             ),
     *             @OA\Property(property="message", type="string", example="Veuillez confirmer le paiement avec votre code PIN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand introuvable",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_001"),
     *                 @OA\Property(property="message", type="string", example="Marchand introuvable")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Solde insuffisant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="WALLET_001"),
     *                 @OA\Property(property="message", type="string", example="Solde insuffisant")
     *             )
     *         )
     *     )
     * )
     */
    public function initierPaiement(InitierPaiementRequest $request)
    {
        $utilisateur = $request->user();
        $result = $this->paiementService->initierPaiement($utilisateur, $request->validated());
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/paiement/{idPaiement}/confirmer-paiement",
     *     summary="Confirmer un paiement",
     *     description="Confirme un paiement avec le code PIN",
     *     operationId="confirmerPaiement",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="idPaiement",
     *         in="path",
     *         required=true,
     *         description="ID du paiement",
     *         @OA\Schema(type="string", example="93027583-73d6-401a-b598-64ce2aede79b")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"codePin"},
     *             @OA\Property(property="codePin", type="string", example="0000", description="Code PIN de 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement confirmé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idTransaction", type="string", example="68b996bb-33c1-4d81-bba0-96793e6c1651"),
     *                 @OA\Property(property="statut", type="string", example="reussie"),
     *                 @OA\Property(property="marchand", type="object",
     *                     @OA\Property(property="nom", type="string", example="Restaurant LE PALACE"),
     *                     @OA\Property(property="numeroTelephone", type="string", example="701234567")
     *                 ),
     *                 @OA\Property(property="montant", type="number", example=5000.00),
     *                 @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-11T11:10:09+00:00"),
     *                 @OA\Property(property="reference", type="string", example="OM20251111111009385638"),
     *                 @OA\Property(property="recu", type="string", example="https://cdn.ompay.sn/recus/68b996bb-33c1-4d81-bba0-96793e6c1651.pdf")
     *             ),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code PIN incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="USER_006"),
     *                 @OA\Property(property="message", type="string", example="Code PIN incorrect")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paiement introuvable",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_010"),
     *                 @OA\Property(property="message", type="string", example="Paiement introuvable ou non autorisé")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Paiement expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_009"),
     *                 @OA\Property(property="message", type="string", example="Paiement expiré")
     *             )
     *         )
     *     )
     * )
     */
    public function confirmerPaiement(ConfirmerPaiementRequest $request, $idPaiement)
    {
        $utilisateur = $request->user();
        $result = $this->paiementService->confirmerPaiement($utilisateur, $idPaiement, $request->codePin);
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Delete(
     *     path="/paiement/{idPaiement}/annuler-paiement",
     *     summary="Annuler un paiement",
     *     description="Annule un paiement en attente de confirmation",
     *     operationId="annulerPaiement",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="idPaiement",
     *         in="path",
     *         required=true,
     *         description="ID du paiement",
     *         @OA\Schema(type="string", example="93027583-73d6-401a-b598-64ce2aede79b")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement annulé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement annulé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paiement introuvable",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_010"),
     *                 @OA\Property(property="message", type="string", example="Paiement introuvable ou non autorisé")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Paiement expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="PAYMENT_009"),
     *                 @OA\Property(property="message", type="string", example="Paiement expiré")
     *             )
     *         )
     *     )
     * )
     */
    public function annulerPaiement(Request $request, $idPaiement)
    {
        $utilisateur = $request->user();
        $result = $this->paiementService->annulerPaiement($utilisateur, $idPaiement);
        return $this->responseFromResult($result);
    }
}
