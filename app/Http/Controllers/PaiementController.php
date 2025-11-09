<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Transaction;
use App\Models\Marchand;
use App\Models\QRCode;
use App\Models\CodePaiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaiementController extends Controller
{
    // 4.1 Lister les Catégories de Marchands
    public function listerCategories(Request $request)
    {
        // Mongo driver doesn't support selectRaw/groupBy like SQL.
        // Load categories and aggregate counts in PHP to remain compatible with MongoDB.
        $categories = Marchand::all()->groupBy('categorie')->map(function ($items, $categorie) {
            return [
                'idCategorie' => 'cat_' . strtolower(str_replace(' ', '_', $categorie)),
                'nom' => $categorie,
                'description' => 'Description de ' . $categorie,
                'icone' => strtolower(str_replace(' ', '_', $categorie)),
                'nombreMarchands' => count($items),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories
            ]
        ]);
    }

    // 4.2 Scanner un QR Code
    public function scannerQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'donneesQR' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données QR invalides',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        // Parser les données QR (format: OM_PAY_{idMarchand}_{montant}_{timestamp}_{signature})
        $parts = explode('_', $request->donneesQR);
        if (count($parts) !== 6 || $parts[0] !== 'OM' || $parts[1] !== 'PAY') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_003',
                    'message' => 'QR Code invalide'
                ]
            ], 422);
        }

        $idMarchand = $parts[2];
        $montant = (int) $parts[3];
        $timestamp = $parts[4];
        $signature = $parts[5];

        $marchand = Marchand::find($idMarchand);
        if (!$marchand) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_001',
                    'message' => 'Marchand introuvable'
                ]
            ], 404);
        }

        // Vérifier si le QR n'est pas expiré (5 minutes)
        $qrTime = Carbon::createFromTimestamp($timestamp);
        if (Carbon::now()->diffInMinutes($qrTime) > 5) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_004',
                    'message' => 'QR Code expiré'
                ]
            ], 422);
        }

        $idScan = 'scn_' . Str::random(10);

        return response()->json([
            'success' => true,
            'data' => [
                'idScan' => $idScan,
                'marchand' => [
                    'idMarchand' => $marchand->idMarchand,
                    'nom' => $marchand->nom,
                    'logo' => $marchand->logo,
                ],
                'montant' => $montant,
                'devise' => 'XOF',
                'dateExpiration' => $qrTime->addMinutes(5)->toIso8601String(),
                'valide' => true,
            ],
            'message' => 'QR Code scanné avec succès'
        ]);
    }

    // 4.3 Saisir un Code de Paiement
    public function saisirCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:8|regex:/^[0-9]{8}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Code invalide',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $codePaiement = CodePaiement::where('code', $request->code)
                                    ->where('statut', 'actif')
                                    ->where('dateExpiration', '>', Carbon::now())
                                    ->first();

        if (!$codePaiement) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_006',
                    'message' => 'Code de paiement invalide'
                ]
            ], 422);
        }

        $marchand = $codePaiement->marchand;
        $idCode = 'cod_' . Str::random(10);

        return response()->json([
            'success' => true,
            'data' => [
                'idCode' => $idCode,
                'marchand' => [
                    'idMarchand' => $marchand->idMarchand,
                    'nom' => $marchand->nom,
                    'logo' => $marchand->logo,
                ],
                'montant' => $codePaiement->montant,
                'devise' => $codePaiement->devise,
                'dateExpiration' => optional($codePaiement->dateExpiration)?->toIso8601String(),
                'valide' => true,
            ],
            'message' => 'Code validé avec succès'
        ]);
    }

    // 4.4 Initier un Paiement
    public function initierPaiement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idScan' => 'required_without:idCode|string',
            'idCode' => 'required_without:idScan|string',
            'idMarchand' => 'required|string',
            'montant' => 'required|numeric|min:50|max:500000',
            'modePaiement' => 'required|in:qr_code,code',
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
        $marchand = Marchand::find($request->idMarchand);

        if (!$marchand) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_001',
                    'message' => 'Marchand introuvable'
                ]
            ], 404);
        }

        $portefeuille = $utilisateur->portefeuille;

        if ($portefeuille->solde < $request->montant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_001',
                    'message' => 'Solde insuffisant'
                ]
            ], 422);
        }

        $idPaiement = 'pay_' . Str::random(10);

        $paiement = Paiement::create([
            'idPaiement' => $idPaiement,
            'idUtilisateur' => $utilisateur->idUtilisateur,
            'idMarchand' => $request->idMarchand,
            'montant' => $request->montant,
            'devise' => 'XOF',
            'modePaiement' => $request->modePaiement,
            'statut' => 'en_attente_confirmation',
            'dateExpiration' => Carbon::now()->addMinutes(5),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'idPaiement' => $paiement->idPaiement,
                'statut' => $paiement->statut,
                'marchand' => [
                    'idMarchand' => $marchand->idMarchand,
                    'nom' => $marchand->nom,
                    'logo' => $marchand->logo,
                ],
                'montant' => $paiement->montant,
                'frais' => 0, // Frais à la charge du marchand
                'montantTotal' => $paiement->montant,
                'dateExpiration' => optional($paiement->dateExpiration)?->toIso8601String(),
            ],
            'message' => 'Veuillez confirmer le paiement avec votre code PIN'
        ]);
    }

    // 4.5 Confirmer un Paiement
    public function confirmerPaiement(Request $request, $idPaiement)
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
        $paiement = Paiement::where('idPaiement', $idPaiement)
                            ->where('idUtilisateur', $utilisateur->idUtilisateur)
                            ->where('statut', 'en_attente_confirmation')
                            ->first();

        if (!$paiement) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_010',
                    'message' => 'Paiement introuvable ou déjà confirmé'
                ]
            ], 404);
        }

        if (Carbon::now()->isAfter($paiement->dateExpiration)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_009',
                    'message' => 'Paiement expiré'
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

        DB::transaction(function () use ($paiement, $utilisateur) {
            $portefeuille = $utilisateur->portefeuille;
            $marchand = $paiement->marchand;

            // Débiter l'utilisateur
            $portefeuille->decrement('solde', $paiement->montant);

            // Créditer le marchand (simulé)
            // $marchand->portefeuille->increment('solde', $paiement->montant);

            // Créer la transaction
            $idTransaction = 'txn_' . Str::random(10);
            Transaction::create([
                'idTransaction' => $idTransaction,
                'idUtilisateur' => $utilisateur->idUtilisateur,
                'type' => 'paiement',
                'montant' => $paiement->montant,
                'devise' => $paiement->devise,
                'nomMarchand' => $marchand->nom,
                'categorieMarchand' => $marchand->categorie,
                'statut' => 'termine',
                'dateTransaction' => Carbon::now(),
                'reference' => 'OM' . date('YmdHis') . rand(100000, 999999),
                'frais' => 0,
            ]);

            // Mettre à jour le paiement
            $paiement->update([
                'statut' => 'termine',
                'idTransaction' => $idTransaction,
            ]);

            // Marquer le code comme utilisé si c'était un paiement par code
            if ($paiement->modePaiement === 'code') {
                // Trouver et marquer le code comme utilisé
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'idTransaction' => $paiement->idTransaction,
                'statut' => 'termine',
                'marchand' => [
                    'nom' => $paiement->marchand->nom,
                    'numeroTelephone' => $paiement->marchand->numeroTelephone,
                ],
                'montant' => $paiement->montant,
                'dateTransaction' => Carbon::now()->toIso8601String(),
                'reference' => $paiement->transaction->reference ?? 'REF',
                'recu' => 'https://cdn.ompay.sn/recus/' . $paiement->idTransaction . '.pdf',
            ],
            'message' => 'Paiement effectué avec succès'
        ]);
    }

    // 4.6 Annuler un Paiement
    public function annulerPaiement(Request $request, $idPaiement)
    {
        $utilisateur = $request->user();
        $paiement = Paiement::where('idPaiement', $idPaiement)
                            ->where('idUtilisateur', $utilisateur->idUtilisateur)
                            ->where('statut', 'en_attente_confirmation')
                            ->first();

        if (!$paiement) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_010',
                    'message' => 'Paiement introuvable ou déjà annulé'
                ]
            ], 404);
        }

        if (Carbon::now()->isAfter($paiement->dateExpiration)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_009',
                    'message' => 'Paiement expiré'
                ]
            ], 422);
        }

        $paiement->update(['statut' => 'annule']);

        return response()->json([
            'success' => true,
            'message' => 'Paiement annulé avec succès'
        ]);
    }
}
