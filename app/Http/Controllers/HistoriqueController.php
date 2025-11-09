<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoriqueController extends Controller
{
    // 6.1 Rechercher dans l'Historique
    public function rechercher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|max:100',
            'type' => 'nullable|in:transfert,paiement',
            'montantMin' => 'nullable|numeric|min:0',
            'montantMax' => 'nullable|numeric|min:0',
            'dateDebut' => 'nullable|date_format:Y-m-d',
            'dateFin' => 'nullable|date_format:Y-m-d',
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

        $query = Transaction::where('idUtilisateur', $utilisateur->idUtilisateur);

        if ($request->has('q')) {
            $q = $request->q;
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('reference', 'like', "%{$q}%")
                         ->orWhere('numeroTelephoneDestinataire', 'like', "%{$q}%")
                         ->orWhere('nomDestinataire', 'like', "%{$q}%")
                         ->orWhere('nomMarchand', 'like', "%{$q}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('montantMin')) {
            $query->where('montant', '>=', $request->montantMin);
        }

        if ($request->has('montantMax')) {
            $query->where('montant', '<=', $request->montantMax);
        }

        if ($request->has('dateDebut')) {
            $query->whereDate('dateTransaction', '>=', $request->dateDebut);
        }

        if ($request->has('dateFin')) {
            $query->whereDate('dateTransaction', '<=', $request->dateFin);
        }

        $resultats = $query->orderBy('dateTransaction', 'desc')
                           ->limit(100) // Limiter les résultats
                           ->get();

        $data = $resultats->map(function ($transaction) {
            $destinataire = null;
            if ($transaction->type === 'transfert') {
                $destinataire = [
                    'nom' => $transaction->nomDestinataire,
                    'numeroTelephone' => $transaction->numeroTelephoneDestinataire,
                ];
            }

            return [
                'idTransaction' => $transaction->idTransaction,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'destinataire' => $destinataire,
                'dateTransaction' => $transaction->dateTransaction->toISOString(),
                'reference' => $transaction->reference,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'resultats' => $data,
                'nombreResultats' => $data->count(),
            ]
        ]);
    }
}
