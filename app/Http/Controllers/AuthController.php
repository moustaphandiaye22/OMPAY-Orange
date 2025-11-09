<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Models\Authentification;
use App\Http\Requests\InscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    // 1.1 Inscription
    public function inscription(InscriptionRequest $request)
    {

        // Générer OTP (simulé)
        $otp = rand(100000, 999999);

        // Use snake_case attribute names to match the models' fillable fields
        $utilisateur = Utilisateur::create([
            'numero_telephone' => $request->numeroTelephone,
            'prenom' => $request->prenom,
            'nom' => $request->nom,
            'email' => $request->email,
            'code_pin' => Hash::make($request->codePin),
            'numero_cni' => $request->numeroCNI,
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Ici, envoyer OTP par SMS (simulé)

        return response()->json([
            'success' => true,
            'data' => [
                // return the model primary key and canonical response keys
                'idUtilisateur' => $utilisateur->getKey(),
                'numeroTelephone' => $utilisateur->numero_telephone,
                'otpEnvoye' => true,
                'dateExpiration' => optional($utilisateur->otp_expires_at)?->toIso8601String(),
            ],
            'message' => 'OTP envoyé par SMS'
        ], 201);
    }

    // 1.2 Vérification OTP
    public function verificationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numeroTelephone' => 'required|string|regex:/^\+221[0-9]{9}$/',
            'codeOTP' => 'required|string|size:6|regex:/^[0-9]{6}$/',
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

        $utilisateur = Utilisateur::where('numero_telephone', $request->numeroTelephone)->first();

    if (!$utilisateur || $utilisateur->otp != $request->codeOTP || ($utilisateur->otp_expires_at && Carbon::now()->isAfter($utilisateur->otp_expires_at))) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_004',
                    'message' => 'OTP invalide ou expiré'
                ]
            ], 401);
        }

        // Générer tokens JWT (simulé)
        $accessToken = Str::random(64);
        $refreshToken = Str::random(64);

        Authentification::create([
            'id_utilisateur' => $utilisateur->getKey(),
            'jeton_acces' => $accessToken,
            'jeton_rafraichissement' => $refreshToken,
            'date_expiration' => Carbon::now()->addHours(24),
        ]);

        $utilisateur->update(['statut_kyc' => 'verifie']);

        return response()->json([
            'success' => true,
            'data' => [
                'jetonAcces' => $accessToken,
                'jetonRafraichissement' => $refreshToken,
                'utilisateur' => [
                    'idUtilisateur' => $utilisateur->getKey(),
                    'numeroTelephone' => $utilisateur->numero_telephone,
                    'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                    'idPortefeuille' => optional($utilisateur->portefeuille)->getKey(),
                ]
            ],
            'message' => 'Authentification réussie'
        ]);
    }

    // 1.3 Connexion
    public function connexion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numeroTelephone' => 'required|string|regex:/^\+221[0-9]{9}$/',
            'codePin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
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

        $utilisateur = Utilisateur::where('numero_telephone', $request->numeroTelephone)->first();

        if (!$utilisateur || !Hash::check($request->codePin, $utilisateur->code_pin)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_001',
                    'message' => 'Identifiants invalides'
                ]
            ], 401);
        }
        if ($utilisateur->statut_kyc !== 'verifie') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_002',
                    'message' => 'Compte non vérifié'
                ]
            ], 401);
        }

        // Générer tokens
        $accessToken = Str::random(64);
        $refreshToken = Str::random(64);

        Authentification::create([
            'id_utilisateur' => $utilisateur->getKey(),
            'jeton_acces' => $accessToken,
            'jeton_rafraichissement' => $refreshToken,
            'date_expiration' => Carbon::now()->addHours(24),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'jetonAcces' => $accessToken,
                'jetonRafraichissement' => $refreshToken,
                'utilisateur' => [
                    'idUtilisateur' => $utilisateur->getKey(),
                    'numeroTelephone' => $utilisateur->numero_telephone,
                    'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                    'email' => $utilisateur->email,
                    'statutKYC' => $utilisateur->statut_kyc,
                    'biometrieActivee' => $utilisateur->biometrie_activee,
                ]
            ],
            'message' => 'Connexion réussie'
        ]);
    }

    // 1.4 Rafraîchir le Token
    public function rafraichir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jetonRafraichissement' => 'required|string',
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

        $auth = Authentification::where('jeton_rafraichissement', $request->jetonRafraichissement)->first();

    if (!$auth || ($auth->date_expiration && Carbon::now()->isAfter($auth->date_expiration))) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_006',
                    'message' => 'Token invalide ou expiré'
                ]
            ], 401);
        }

        $newAccessToken = Str::random(64);
        $newRefreshToken = Str::random(64);

        $auth->update([
            'jeton_acces' => $newAccessToken,
            'jeton_rafraichissement' => $newRefreshToken,
            'date_expiration' => Carbon::now()->addHours(24),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'jetonAcces' => $newAccessToken,
                'jetonRafraichissement' => $newRefreshToken,
            ]
        ]);
    }

    // 1.5 Déconnexion
    public function deconnexion(Request $request)
    {
        // Supprimer le token d'accès (simulé)
        // Dans un vrai système, invalider le token

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    // 1.6 Consulter Profil
    public function consulterProfil(Request $request)
    {
        $utilisateur = $request->user(); // Assumer middleware d'auth

        return response()->json([
            'success' => true,
            'data' => [
                'idUtilisateur' => $utilisateur->getKey(),
                'numeroTelephone' => $utilisateur->numero_telephone,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'email' => $utilisateur->email,
                'numeroCNI' => $utilisateur->numero_cni ?? null,
                'statutKYC' => $utilisateur->statut_kyc ?? null,
                'biometrieActivee' => $utilisateur->biometrie_activee ?? false,
                'dateCreation' => optional($utilisateur->date_creation)?->toIso8601String(),
                'derniereConnexion' => optional($utilisateur->derniere_connexion)?->toIso8601String(),
            ]
        ]);
    }

    // 1.7 Mettre à jour Profil
    public function mettreAJourProfil(Request $request)
    {
        $utilisateur = $request->user();

        $validator = Validator::make($request->all(), [
            'prenom' => 'sometimes|string|min:2|max:50|alpha',
            'nom' => 'sometimes|string|min:2|max:50|alpha',
            // ignore current user by primary key (_id for Mongo)
            'email' => 'sometimes|email|unique:utilisateurs,email,' . $utilisateur->getKey() . ',_id',
            'codePin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
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

    if (!Hash::check($request->codePin, $utilisateur->code_pin)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_006',
                    'message' => 'PIN incorrect'
                ]
            ], 401);
        }

        $utilisateur->update($request->only(['prenom', 'nom', 'email']));

        return response()->json([
            'success' => true,
            'data' => [
                'idUtilisateur' => $utilisateur->getKey(),
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'email' => $utilisateur->email,
            ],
            'message' => 'Profil mis à jour avec succès'
        ]);
    }

    // 1.8 Changer le Code PIN
    public function changerPin(Request $request)
    {
        $utilisateur = $request->user();

        $validator = Validator::make($request->all(), [
            'ancienPin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
            'nouveauPin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
            'confirmationPin' => 'required|string|same:nouveauPin',
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

    if (!Hash::check($request->ancienPin, $utilisateur->code_pin)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_006',
                    'message' => 'Ancien PIN incorrect'
                ]
            ], 401);
        }

    if (Hash::check($request->nouveauPin, $utilisateur->code_pin)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_007',
                    'message' => 'Nouveau PIN identique à l\'ancien'
                ]
            ], 422);
        }

    $utilisateur->update(['code_pin' => Hash::make($request->nouveauPin)]);

        return response()->json([
            'success' => true,
            'message' => 'Code PIN modifié avec succès'
        ]);
    }

    // 1.9 Activer la Biométrie
    public function activerBiometrie(Request $request)
    {
        $utilisateur = $request->user();

        $validator = Validator::make($request->all(), [
            'codePin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
            'jetonBiometrique' => 'required|string',
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

    if (!Hash::check($request->codePin, $utilisateur->code_pin)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_006',
                    'message' => 'PIN incorrect'
                ]
            ], 401);
        }

        $utilisateur->update([
            'biometrie_activee' => true,
            'jeton_biometrique' => $request->jetonBiometrique,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'biometrieActivee' => true,
            ],
            'message' => 'Biométrie activée avec succès'
        ]);
    }
}
