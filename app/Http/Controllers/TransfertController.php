<?php

namespace App\Http\Controllers;

use App\Models\Transfert;
use App\Models\Transaction;
use App\Models\Utilisateur;
use App\Models\Portefeuille;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransfertController extends Controller
{
    // 3.1 Vérifier un Destinataire
    public function verifierDestinataire(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numeroTelephone' => 'required|string|regex:/^\+221[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Numéro de téléphone invalide',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $destinataire = Utilisateur::where('numeroTelephone', $request->numeroTelephone)->first();

        if (!$destinataire) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_001',
                    'message' => 'Destinataire introuvable'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'estValide' => true,
                'nom' => $destinataire->prenom . ' ' . $destinataire->nom,
                'numeroTelephone' => $destinataire->numeroTelephone,
                'operateur' => 'Orange', // Simulé
            ]
        ]);
    }

    // 3.2 Initier un Transfert
    public function initierTransfert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephoneDestinataire' => 'required|string|regex:/^\+221[0-9]{9}$/',
            'montant' => 'required|numeric|min:100|max:1000000',
            'devise' => 'required|string|in:XOF',
            'note' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $utilisateur = $request->user();
        $destinataire = Utilisateur::where('numeroTelephone', $request->telephoneDestinataire)->first();

        if (!$destinataire) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_001',
                    'message' => 'Destinataire introuvable'
                ]
            ], 404);
        }

        if ($destinataire->idUtilisateur === $utilisateur->idUtilisateur) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_003',
                    'message' => 'Transfert à soi-même interdit'
                ]
            ], 422);
        }

        $portefeuille = $utilisateur->portefeuille;
        $frais = $this->calculerFrais($request->montant);

        if ($portefeuille->solde < ($request->montant + $frais)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Solde insuffisant'
                ]
            ], 422);
        }

        $idTransfert = 'trf_' . Str::random(10);

        $transfert = Transfert::create([
            'idTransfert' => $idTransfert,
            'idUtilisateur' => $utilisateur->idUtilisateur,
            'telephoneDestinataire' => $request->telephoneDestinataire,
            'montant' => $request->montant,
            'devise' => $request->devise,
            'frais' => $frais,
            'note' => $request->note,
            'statut' => 'en_attente_confirmation',
            'dateExpiration' => Carbon::now()->addMinutes(5),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'idTransfert' => $transfert->idTransfert,
                'statut' => $transfert->statut,
                'montant' => $transfert->montant,
                'frais' => $transfert->frais,
                'montantTotal' => $transfert->montant + $transfert->frais,
                'destinataire' => [
                    'numeroTelephone' => $destinataire->numeroTelephone,
                    'nom' => $destinataire->prenom . ' ' . $destinataire->nom,
                ],
                'dateExpiration' => $transfert->dateExpiration->toISOString(),
            ],
            'message' => 'Veuillez confirmer le transfert avec votre code PIN'
        ]);
    }

    // 3.3 Confirmer un Transfert
    public function confirmerTransfert(Request $request, $idTransfert)
    {
        $validator = Validator::make($request->all(), [
            'codePin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Code PIN requis',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $utilisateur = $request->user();
        $transfert = Transfert::where('idTransfert', $idTransfert)
                              ->where('idUtilisateur', $utilisateur->idUtilisateur)
                              ->where('statut', 'en_attente_confirmation')
                              ->first();

        if (!$transfert) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_005',
                    'message' => 'Transfert introuvable ou déjà confirmé'
                ]
            ], 404);
        }

        if (Carbon::now()->isAfter($transfert->dateExpiration)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_004',
                    'message' => 'Transfert expiré'
                ]
            ], 422);
        }

        if (!Hash::check($request->codePin, $utilisateur->codePin)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_006',
                    'message' => 'Code PIN incorrect'
                ]
            ], 401);
        }

        DB::transaction(function () use ($transfert, $utilisateur) {
            $portefeuilleExpediteur = $utilisateur->portefeuille;
            $destinataire = Utilisateur::where('numeroTelephone', $transfert->telephoneDestinataire)->first();
            $portefeuilleDestinataire = $destinataire->portefeuille;

            // Débiter l'expéditeur
            $portefeuilleExpediteur->decrement('solde', $transfert->montant + $transfert->frais);

            // Créditer le destinataire
            $portefeuilleDestinataire->increment('solde', $transfert->montant);

            // Créer la transaction
            $idTransaction = 'txn_' . Str::random(10);
            Transaction::create([
                'idTransaction' => $idTransaction,
                'idUtilisateur' => $utilisateur->idUtilisateur,
                'type' => 'transfert',
                'montant' => $transfert->montant,
                'devise' => $transfert->devise,
                'numeroTelephoneDestinataire' => $transfert->telephoneDestinataire,
                'nomDestinataire' => $destinataire->prenom . ' ' . $destinataire->nom,
                'statut' => 'termine',
                'dateTransaction' => Carbon::now(),
                'reference' => 'OM' . date('YmdHis') . rand(100000, 999999),
                'frais' => $transfert->frais,
                'note' => $transfert->note,
            ]);

            // Mettre à jour le transfert
            $transfert->update([
                'statut' => 'termine',
                'idTransaction' => $idTransaction,
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'idTransaction' => $transfert->idTransaction,
                'statut' => 'termine',
                'montant' => $transfert->montant,
                'destinataire' => [
                    'numeroTelephone' => $transfert->telephoneDestinataire,
                    'nom' => $transfert->nomDestinataire ?? 'Destinataire',
                ],
                'dateTransaction' => Carbon::now()->toISOString(),
                'reference' => $transfert->transaction->reference ?? 'REF',
                'recu' => 'https://cdn.ompay.sn/recus/' . $transfert->idTransaction . '.pdf',
            ],
            'message' => 'Transfert effectué avec succès'
        ]);
    }

    // 3.4 Annuler un Transfert
    public function annulerTransfert(Request $request, $idTransfert)
    {
        $utilisateur = $request->user();
        $transfert = Transfert::where('idTransfert', $idTransfert)
                              ->where('idUtilisateur', $utilisateur->idUtilisateur)
                              ->where('statut', 'en_attente_confirmation')
                              ->first();

        if (!$transfert) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_006',
                    'message' => 'Transfert introuvable ou déjà annulé'
                ]
            ], 404);
        }

        if (Carbon::now()->isAfter($transfert->dateExpiration)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_004',
                    'message' => 'Transfert expiré'
                ]
            ], 422);
        }

        $transfert->update(['statut' => 'annule']);

        return response()->json([
            'success' => true,
            'message' => 'Transfert annulé avec succès'
        ]);
    }

    private function calculerFrais($montant)
    {
        if ($montant <= 5000) return 0;
        if ($montant <= 25000) return 0;
        if ($montant <= 50000) return 0;
        if ($montant <= 100000) return 100;
        return 200;
    }
}
