<?php

namespace App\Http\Controllers;

use App\Interfaces\TransfertServiceInterface;
use App\Http\Requests\EffectuerTransfertWithUserRequest;
use App\Http\Requests\AnnulerTransfertWithUserRequest;
use App\Models\Utilisateur;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Transferts",
 *     description="Endpoints pour les transferts d'argent entre utilisateurs"
 * )
 */
class TransfertController extends Controller
{
    protected $transfertService;

    public function __construct(TransfertServiceInterface $transfertService)
    {
        $this->transfertService = $transfertService;
    }

    // Removed helper method as we use authenticated user


    /**
     * @OA\Post(
     *     path="/transfert/effectuer-transfert",
     *     summary="Effectuer un transfert",
     *     description="Effectue un transfert d'argent complet vers un destinataire pour l'utilisateur authentifié",
     *     operationId="effectuerTransfert",
     *     tags={"Transferts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephoneDestinataire","montant","devise","codePin"},
     *             @OA\Property(property="telephoneDestinataire", type="string", example="+221771234567", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant à transférer"),
     *             @OA\Property(property="devise", type="string", example="XOF", enum={"FCFA", "XOF"}, description="Devise du transfert"),
     *             @OA\Property(property="note", type="string", example="Paiement loyer", description="Note optionnelle"),
     *             @OA\Property(property="codePin", type="string", example="1234", description="Code PIN de confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idTransaction", type="string", example="65d75949-a025-4d38-812e-7ffb45b00012"),
     *                 @OA\Property(property="idTransfert", type="string", example="b81c4b38-bd28-4ed2-a014-aa6d410af243"),
     *                 @OA\Property(property="statut", type="string", example="reussie"),
     *                 @OA\Property(property="montant", type="integer", example=1000),
     *                 @OA\Property(property="frais", type="integer", example=0),
     *                 @OA\Property(property="montantTotal", type="integer", example=1000),
     *                 @OA\Property(property="destinataire", type="object",
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="nom", type="string", example="Admin Orange Money")
     *                 ),
     *                 @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-12T21:49:06+00:00"),
     *                 @OA\Property(property="reference", type="string", example="OM20251112214905902318"),
     *                 @OA\Property(property="recu", type="string", example="https://cdn.ompay.sn/recus/65d75949-a025-4d38-812e-7ffb45b00012.pdf")
     *             ),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Solde insuffisant ou code PIN incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="INSUFFICIENT_BALANCE"),
     *                 @OA\Property(property="message", type="string", example="Solde insuffisant pour effectuer ce transfert")
     *             )
     *         )
     *     )
     * )
     */
    // 3.2 Effectuer un Transfert (fusion initier + confirmer)
    public function effectuerTransfert(EffectuerTransfertWithUserRequest $request)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();

            $result = $this->transfertService->effectuerTransfert($utilisateur, $data);
            return $this->responseFromResult($result);
        } catch (\Exception $e) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erreur: ' . $e->getMessage()
                ],
                'status' => 500
            ]);
        }
    }

    /**
     * @OA\Delete(
     *     path="/transfert/{id}/annuler-transfert",
     *     summary="Annuler un transfert",
     *     description="Annule un transfert en attente pour l'utilisateur authentifié",
     *     operationId="annulerTransfert",
     *     tags={"Transferts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du transfert à annuler",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert annulé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert annulé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Transfert ne peut pas être annulé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="CANNOT_CANCEL_TRANSFER"),
     *                 @OA\Property(property="message", type="string", example="Ce transfert ne peut pas être annulé")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transfert non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="TRANSFER_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Transfert non trouvé")
     *             )
     *         )
     *     )
     * )
     */
    // 3.4 Annuler un Transfert
    public function annulerTransfert(Request $request, $id)
    {
        // Validation de l'UUID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Format d\'identifiant invalide'
                ],
                'status' => 400
            ]);
        }

        $utilisateur = $request->user();

        try {
            $result = $this->transfertService->annulerTransfert($utilisateur, $id);
            return $this->responseFromResult($result);
        } catch (\Exception $e) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erreur interne du serveur'
                ],
                'status' => 500
            ]);
        }
    }
}
