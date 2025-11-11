<?php

namespace App\Http\Controllers;

use App\Interfaces\PortefeuilleServiceInterface;
use Illuminate\Http\Request;

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
     * @OA\Get(
     *     path="/portefeuille/solde",
     *     summary="Consulter le solde du portefeuille",
     *     description="Récupère le solde actuel du portefeuille de l'utilisateur",
     *     operationId="consulterSolde",
     *     tags={"Portefeuille"},
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=91500.00),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="dateDerniereMiseAJour", type="string", format="date-time", example="2025-11-10T12:00:00Z")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 2.1 Consulter le Solde
    public function consulterSolde(Request $request)
    {
        $utilisateur = $request->user();
        $result = $this->portefeuilleService->consulterSolde($utilisateur);

        return $this->responseFromResult($result);
    }

    /**
     * @OA\Get(
     *     path="/portefeuille/transactions",
     *     summary="Historique des transactions",
     *     description="Récupère l'historique des transactions de l'utilisateur avec pagination et filtres",
     *     operationId="historiqueTransactions",
     *     tags={"Portefeuille"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limite",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"transfert", "paiement", "tous"})
     *     ),
     *     @OA\Parameter(
     *         name="dateDebut",
     *         in="query",
     *         description="Date de début (format Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="dateFin",
     *         in="query",
     *         description="Date de fin (format Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Statut de la transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"en_attente", "termine", "echoue", "annule"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", example="uuid"),
     *                         @OA\Property(property="type", type="string", example="transfert"),
     *                         @OA\Property(property="montant", type="number", example=5000),
     *                         @OA\Property(property="devise", type="string", example="XOF"),
     *                         @OA\Property(property="statut", type="string", example="termine"),
     *                         @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-11-10T12:00:00Z"),
     *                         @OA\Property(property="reference", type="string", example="TRF123456789")
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="page", type="integer", example=1),
     *                     @OA\Property(property="limite", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="pages", type="integer", example=8)
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 2.2 Historique des Transactions
    public function historiqueTransactions(Request $request)
    {
        $utilisateur = $request->user();
        $page = $request->get('page', 1);
        $limite = $request->get('limite', 20);

        $filters = $request->only(['type', 'dateDebut', 'dateFin', 'statut']);

        $result = $this->portefeuilleService->historiqueTransactions($utilisateur, $filters, $page, $limite);

        return $this->responseFromResult($result);
    }

    /**
     * @OA\Get(
     *     path="/portefeuille/transactions/{idTransaction}",
     *     summary="Détails d'une transaction",
     *     description="Récupère les détails complets d'une transaction spécifique",
     *     operationId="detailsTransaction",
     *     tags={"Portefeuille"},
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
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="type", type="string", example="transfert"),
     *                     @OA\Property(property="montant", type="number", example=5000),
     *                     @OA\Property(property="frais", type="number", example=100),
     *                     @OA\Property(property="devise", type="string", example="XOF"),
     *                     @OA\Property(property="statut", type="string", example="termine"),
     *                     @OA\Property(property="reference", type="string", example="TRF123456789"),
     *                     @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-11-10T12:00:00Z"),
     *                     @OA\Property(property="dateFinalisation", type="string", format="date-time", example="2025-11-10T12:05:00Z"),
     *                     @OA\Property(property="destinataire", type="object",
     *                         @OA\Property(property="nom", type="string", example="Admin Orange Money"),
     *                         @OA\Property(property="numeroTelephone", type="string", example="+221771234567")
     *                     ),
     *                     @OA\Property(property="marchand", type="object",
     *                         @OA\Property(property="nom", type="string", example="Boutique Express"),
     *                         @OA\Property(property="numeroMarchand", type="string", example="MERCHANT123")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="TRANSACTION_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Transaction non trouvée")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 2.3 Détails d'une Transaction
    public function detailsTransaction(Request $request, $idTransaction)
    {
        $utilisateur = $request->user();
        $result = $this->portefeuilleService->detailsTransaction($utilisateur, $idTransaction);

        return $this->responseFromResult($result);
    }
}
