<?php

namespace App\Http\Controllers;

use App\Interfaces\TransfertServiceInterface;
use App\Http\Requests\InitierTransfertRequest;
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


    /**
     * @OA\Post(
     *     path="/transfert/effectuer-transfert",
     *     summary="Effectuer un transfert",
     *     description="Initie et confirme un transfert d'argent vers un destinataire",
     *     operationId="effectuerTransfert",
     *     tags={"Transferts"},
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
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transfert", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="reference", type="string", example="TRF123456789"),
     *                     @OA\Property(property="montant", type="number", example=5000),
     *                     @OA\Property(property="frais", type="number", example=100),
     *                     @OA\Property(property="montantTotal", type="number", example=5100),
     *                     @OA\Property(property="destinataire", type="object",
     *                         @OA\Property(property="nom", type="string", example="Admin Orange Money"),
     *                         @OA\Property(property="numeroTelephone", type="string", example="+221771234567")
     *                     ),
     *                     @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-11-10T12:00:00Z")
     *                 )
     *             )
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
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 3.2 Effectuer un Transfert (fusion initier + confirmer)
    public function effectuerTransfert(InitierTransfertRequest $request)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();

            // Ajouter le code PIN aux données validées
            $data['codePin'] = $request->codePin;

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
     *     path="/transfert/{idTransfert}/annuler-transfert",
     *     summary="Annuler un transfert",
     *     description="Annule un transfert en attente",
     *     operationId="annulerTransfert",
     *     tags={"Transferts"},
     *     @OA\Parameter(
     *         name="idTransfert",
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
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 3.4 Annuler un Transfert
    public function annulerTransfert(Request $request, $idTransfert)
    {
        // Validation de l'UUID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idTransfert)) {
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
            $result = $this->transfertService->annulerTransfert($utilisateur, $idTransfert);
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
