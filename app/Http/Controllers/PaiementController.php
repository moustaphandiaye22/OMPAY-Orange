<?php

namespace App\Http\Controllers;

use App\Interfaces\PaiementServiceInterface;
use App\Http\Requests\EffectuerPaiementRequest;
use App\Models\Utilisateur;
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
     * Helper method to get user by ID
     */
    private function getUtilisateurById($idUtilisateur)
    {
        return Utilisateur::find($idUtilisateur);
    }


    /**
     * @OA\Post(
     *     path="/paiement/effectuer-paiement",
     *     summary="Effectuer un paiement",
     *     description="Effectue un paiement complet en une seule opération. Supporte les paiements par QR code, code de paiement ou numéro de téléphone.",
     *     operationId="effectuerPaiement",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"idUtilisateur","montant","devise","codePin","modePaiement"},
     *             @OA\Property(property="idUtilisateur", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID de l'utilisateur"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant du paiement"),
     *             @OA\Property(property="devise", type="string", example="XOF", description="Devise"),
     *             @OA\Property(property="codePin", type="string", example="1234", description="Code PIN de confirmation"),
     *             @OA\Property(property="modePaiement", type="string", enum={"qr_code", "code", "telephone"}, example="qr_code", description="Mode de paiement"),
     *             @OA\Property(property="donneesQR", type="string", example="OM_PAY_MCHABCDEFGH_500_1762980617_signature", description="Données du QR code (requis si modePaiement = qr_code)"),
     *             @OA\Property(property="code", type="string", example="VADURH", description="Code de paiement (requis si modePaiement = code)"),
     *             @OA\Property(property="numeroTelephone", type="string", example="772345678", description="Numéro de téléphone du marchand (requis si modePaiement = telephone)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idTransaction", type="string", example="5718e7d5-620e-421c-bff5-f241e228b143"),
     *                 @OA\Property(property="statut", type="string", example="termine"),
     *                 @OA\Property(property="marchand", type="object",
     *                     @OA\Property(property="nom", type="string", example="Boutique Orange"),
     *                     @OA\Property(property="numeroTelephone", type="string", example="772345678")
     *                 ),
     *                 @OA\Property(property="montant", type="string", example="500.00"),
     *                 @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-12T21:31:27+00:00"),
     *                 @OA\Property(property="reference", type="string", example="OM20251112213127143829"),
     *                 @OA\Property(property="recu", type="string", example="https://cdn.ompay.sn/recus/5718e7d5-620e-421c-bff5-f241e228b143.pdf"),
     *                 @OA\Property(property="modePaiement", type="string", example="telephone")
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
     *         description="Marchand ou utilisateur introuvable",
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
     *         description="Solde insuffisant ou données invalides",
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
    // 4.1 Effectuer un Paiement (fusion initier + confirmer)
    public function effectuerPaiement(EffectuerPaiementRequest $request)
    {
        $utilisateur = $this->getUtilisateurById($request->idUtilisateur);
        if (!$utilisateur) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'Utilisateur non trouvé'
                ],
                'status' => 404
            ]);
        }

        $data = $request->validated();
        unset($data['idUtilisateur']); // Remove idUtilisateur from data passed to service

        $result = $this->paiementService->effectuerPaiement($utilisateur, $data);
        return $this->responseFromResult($result);
    }

}
