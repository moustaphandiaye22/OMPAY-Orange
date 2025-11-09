<?php

namespace App\Http\Controllers;

use App\Models\Portefeuille;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PortefeuilleController extends Controller
{
    // 2.1 Consulter le Solde
    public function consulterSolde(Request $request)
    {
        $utilisateur = $request->user();
        $portefeuille = $utilisateur->portefeuille;

        if (!$portefeuille) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Portefeuille introuvable'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'idPortefeuille' => $portefeuille->idPortefeuille,
                'solde' => $portefeuille->solde,
                'soldeDisponible' => $portefeuille->solde,
                'soldeEnAttente' => 0, // Calculer si nécessaire
                'devise' => $portefeuille->devise,
                'derniereMiseAJour' => $portefeuille->updated_at->toISOString(),
            ]
        ]);
    }

    // 2.2 Historique des Transactions
    public function historiqueTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'sometimes|integer|min:1',
            'limite' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|in:transfert,paiement,tous',
            'dateDebut' => 'sometimes|date_format:Y-m-d',
            'dateFin' => 'sometimes|date_format:Y-m-d',
            'statut' => 'sometimes|in:en_attente,termine,echoue,annule',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Paramètres invalides',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $utilisateur = $request->user();
        $page = $request->get('page', 1);
        $limite = $request->get('limite', 20);

        $query = Transaction::where('idUtilisateur', $utilisateur->idUtilisateur);

        if ($request->has('type') && $request->type !== 'tous') {
            $query->where('type', $request->type);
        }

        if ($request->has('dateDebut')) {
            $query->whereDate('dateTransaction', '>=', $request->dateDebut);
        }

        if ($request->has('dateFin')) {
            $query->whereDate('dateTransaction', '<=', $request->dateFin);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $transactions = $query->orderBy('dateTransaction', 'desc')
                              ->paginate($limite, ['*'], 'page', $page);

        $data = $transactions->map(function ($transaction) {
            $destinataire = null;
            $marchand = null;

            if ($transaction->type === 'transfert') {
                $destinataire = [
                    'numeroTelephone' => $transaction->numeroTelephoneDestinataire,
                    'nom' => $transaction->nomDestinataire,
                ];
            } elseif ($transaction->type === 'paiement') {
                $marchand = [
                    'nom' => $transaction->nomMarchand,
                    'categorie' => $transaction->categorieMarchand,
                ];
            }

            return [
                'idTransaction' => $transaction->idTransaction,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'devise' => $transaction->devise,
                'destinataire' => $destinataire,
                'marchand' => $marchand,
                'statut' => $transaction->statut,
                'dateTransaction' => $transaction->dateTransaction->toISOString(),
                'reference' => $transaction->reference,
                'frais' => $transaction->frais,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $data,
                'pagination' => [
                    'pageActuelle' => $transactions->currentPage(),
                    'totalPages' => $transactions->lastPage(),
                    'totalElements' => $transactions->total(),
                    'elementsParPage' => $transactions->perPage(),
                ]
            ]
        ]);
    }

    // 2.3 Détails d'une Transaction
    public function detailsTransaction(Request $request, $idTransaction)
    {
        $utilisateur = $request->user();

        $transaction = Transaction::where('idTransaction', $idTransaction)
                                  ->where('idUtilisateur', $utilisateur->idUtilisateur)
                                  ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Transaction introuvable'
                ]
            ], 404);
        }

        $expediteur = null;
        $destinataire = null;

        if ($transaction->type === 'transfert') {
            $expediteur = [
                'numeroTelephone' => $utilisateur->numeroTelephone,
                'nom' => $utilisateur->prenom . ' ' . $utilisateur->nom,
            ];
            $destinataire = [
                'numeroTelephone' => $transaction->numeroTelephoneDestinataire,
                'nom' => $transaction->nomDestinataire,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'idTransaction' => $transaction->idTransaction,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'devise' => $transaction->devise,
                'expediteur' => $expediteur,
                'destinataire' => $destinataire,
                'statut' => $transaction->statut,
                'dateTransaction' => $transaction->dateTransaction->toISOString(),
                'reference' => $transaction->reference,
                'frais' => $transaction->frais,
                'note' => $transaction->note,
            ]
        ]);
    }
}
