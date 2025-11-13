<?php

namespace App\Http\Controllers;

use App\Interfaces\PortefeuilleServiceInterface;
use App\Http\Requests\ConsulterSoldeRequest;
use App\Http\Requests\HistoriqueTransactionsRequest;
use App\Http\Requests\DetailsTransactionRequest;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Portefeuille",
 *     description="Endpoints pour la gestion du portefeuille et des transactions"
 * )
 */
class PortefeuilleController extends Controller
{
    protected $portefeuilleService;

    public function __construct(PortefeuilleServiceInterface $portefeuilleService)
    {
        $this->portefeuilleService = $portefeuilleService;
    }

    /**
     * @OA\Post(
     *     path="/portefeuille/{id}/solde",
     *     summary="Consulter le solde du portefeuille",
     *     description="Récupère le solde actuel du portefeuille de l'utilisateur authentifié",
     *     operationId="consulterSolde",
     *     tags={"Portefeuille"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du portefeuille",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idPortefeuille", type="string", format="uuid", example="a0552b7e-869b-4063-8163-f91ead609587"),
     *                 @OA\Property(property="solde", type="string", example="41000.00"),
     *                 @OA\Property(property="devise", type="string", example="FCFA")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portefeuille non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="ACCESS_DENIED"),
     *                 @OA\Property(property="message", type="string", example="Portefeuille non trouvé ou accès refusé")
     *             )
     *         )
     *     )
     * )
     */
    // 2.1 Consulter le Solde
    public function consulterSolde(Request $request, $id)
    {
        Log::info('PortefeuilleController::consulterSolde called', ['portefeuille_id' => $id]);

        // Validate id
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'ID de portefeuille invalide'
                ],
                'status' => 400
            ]);
        }

        $user = $request->user();
        if (!$user->portefeuille || $user->portefeuille->id !== $id) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Portefeuille non trouvé ou accès refusé'
                ],
                'status' => 404
            ]);
        }

        $result = $this->portefeuilleService->consulterSolde($user);

        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/portefeuille/{id}/transactions",
     *     summary="Historique des transactions",
     *     description="Récupère l'historique des transactions du portefeuille de l'utilisateur authentifié avec pagination et filtres",
     *     operationId="historiqueTransactions",
     *     tags={"Portefeuille"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du portefeuille",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="page", type="integer", default=1, minimum=1, example=1, description="Numéro de la page"),
     *             @OA\Property(property="limite", type="integer", default=20, minimum=1, maximum=100, example=20, description="Nombre d'éléments par page"),
     *             @OA\Property(property="type", type="string", enum={"transfert", "paiement", "tous"}, example="transfert", description="Type de transaction"),
     *             @OA\Property(property="dateDebut", type="string", format="date", example="2025-11-01", description="Date de début (format Y-m-d)"),
     *             @OA\Property(property="dateFin", type="string", format="date", example="2025-11-30", description="Date de fin (format Y-m-d)"),
     *             @OA\Property(property="statut", type="string", enum={"en_attente", "termine", "echoue", "annule"}, example="termine", description="Statut de la transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="idTransaction", type="string", example="65d75949-a025-4d38-812e-7ffb45b00012"),
     *                         @OA\Property(property="reference", type="string", example="OM20251112214905902318"),
     *                         @OA\Property(property="montant", type="string", example="1000.00"),
     *                         @OA\Property(property="frais", type="string", example="0.00"),
     *                         @OA\Property(property="montantTotal", type="integer", example=1000),
     *                         @OA\Property(property="devise", type="string", example="XOF"),
     *                         @OA\Property(property="statut", type="string", example="reussie"),
     *                         @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-12T21:49:05+00:00"),
     *                         @OA\Property(property="destinataire", type="object",
     *                             @OA\Property(property="numeroTelephone", type="string", example="+221771234567"),
     *                             @OA\Property(property="nom", type="string", example="Admin Orange Money")
     *                         ),
     *                         @OA\Property(property="marchand", type="object", nullable=true,
     *                             @OA\Property(property="nom", type="string", example="Boutique Orange"),
     *                             @OA\Property(property="categorie", type="string", example="Alimentation")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="pageActuelle", type="integer", example=1),
     *                     @OA\Property(property="totalPages", type="integer", example=1),
     *                     @OA\Property(property="totalElements", type="integer", example=8),
     *                     @OA\Property(property="elementsParPage", type="integer", example=20)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portefeuille non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="ACCESS_DENIED"),
     *                 @OA\Property(property="message", type="string", example="Portefeuille non trouvé ou accès refusé")
     *             )
     *         )
     *     )
     * )
     */
    // 2.2 Historique des Transactions
    public function historiqueTransactions(HistoriqueTransactionsRequest $request, $id)
    {
        Log::info('PortefeuilleController::historiqueTransactions called', ['portefeuille_id' => $id]);

        // Validate id
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'ID de portefeuille invalide'
                ],
                'status' => 400
            ]);
        }

        $user = $request->user();
        if (!$user->portefeuille || $user->portefeuille->id !== $id) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Portefeuille non trouvé ou accès refusé'
                ],
                'status' => 404
            ]);
        }

        $page = $request->get('page', 1);
        $limite = $request->get('limite', 20);

        $filters = $request->only(['type', 'dateDebut', 'dateFin', 'statut']);

        $result = $this->portefeuilleService->historiqueTransactions($user, $filters, $page, $limite);

        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/portefeuille/{id}/transactions/{idTransaction}",
     *     summary="Détails d'une transaction",
     *     description="Récupère les détails complets d'une transaction spécifique pour le portefeuille de l'utilisateur authentifié",
     *     operationId="detailsTransaction",
     *     tags={"Portefeuille"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du portefeuille",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="idTransaction",
     *         in="path",
     *         description="ID de la transaction",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idTransaction", type="string", example="65d75949-a025-4d38-812e-7ffb45b00012"),
     *                 @OA\Property(property="reference", type="string", example="OM20251112214905902318"),
     *                 @OA\Property(property="montant", type="string", example="1000.00"),
     *                 @OA\Property(property="frais", type="string", example="0.00"),
     *                 @OA\Property(property="montantTotal", type="integer", example=1000),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="statut", type="string", example="reussie"),
     *                 @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-12T21:49:05+00:00"),
     *                 @OA\Property(property="expediteur", type="object",
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771411251"),
     *                     @OA\Property(property="nom", type="string", example="Moustapha Ndiaye")
     *                 ),
     *                 @OA\Property(property="destinataire", type="object",
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="nom", type="string", example="Admin Orange Money")
     *                 ),
     *                 @OA\Property(property="note", type="string", example="Test transfer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction ou portefeuille non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="TRANSACTION_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Transaction non trouvée")
     *             )
     *         )
     *     )
     * )
     */
    // 2.3 Détails d'une Transaction
    public function detailsTransaction(Request $request, $id, $idTransaction)
    {
        Log::info('PortefeuilleController::detailsTransaction called', ['portefeuille_id' => $id, 'idTransaction' => $idTransaction]);

        // Validate ids
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idTransaction)) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'ID invalide'
                ],
                'status' => 400
            ]);
        }

        $user = $request->user();
        if (!$user->portefeuille || $user->portefeuille->id !== $id) {
            return $this->responseFromResult([
                'success' => false,
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Portefeuille non trouvé ou accès refusé'
                ],
                'status' => 404
            ]);
        }

        $result = $this->portefeuilleService->detailsTransaction($user, $idTransaction);

        return $this->responseFromResult($result);
    }
}
