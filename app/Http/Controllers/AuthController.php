<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\UserService;
use App\Services\SecurityService;
use App\Http\Requests\InitierInscriptionRequest;
use App\Http\Requests\VerificationOtpRequest;
use App\Http\Requests\ConnexionRequest;
use App\Http\Requests\RafraichirTokenRequest;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'authentification et la gestion des utilisateurs"
 * )
 */
class AuthController extends Controller
{
    protected $authService;
    protected $userService;
    protected $securityService;

    public function __construct(
        AuthService $authService,
        UserService $userService,
        SecurityService $securityService
    ) {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->securityService = $securityService;
    }

    /**
     * @OA\Post(
     *     path="/auth/creercompte",
     *     summary="Créer un compte utilisateur complet",
     *     description="Crée un compte utilisateur avec toutes les informations et envoie un OTP pour activation lors de la première connexion",
     *     operationId="creercompte",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prenom","nom","numeroTelephone","email","numeroCNI"},
     *             @OA\Property(property="prenom", type="string", example="Moustapha", description="Prénom de l'utilisateur"),
     *             @OA\Property(property="nom", type="string", example="Ndiaye", description="Nom de l'utilisateur"),
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771411251", description="Numéro de téléphone au format +221XXXXXXXXX"),
     *             @OA\Property(property="email", type="string", format="email", example="moustapha.ndiaye@email.com", description="Adresse email"),
     *             @OA\Property(property="numeroCNI", type="string", example="7714112511234", description="Numéro CNI à 13 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès, OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès. Code OTP envoyé par SMS pour activation lors de votre première connexion."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idUtilisateur", type="string", example="uuid"),
     *                 @OA\Property(property="numeroTelephone", type="string", example="+221771411251"),
     *                 @OA\Property(property="nomComplet", type="string", example="Moustapha Ndiaye"),
     *                 @OA\Property(property="compteOrangeMoney", type="boolean", example=true),
     *                 @OA\Property(property="otpEnvoye", type="boolean", example=true),
     *                 @OA\Property(property="dateExpiration", type="string", format="date-time", example="2025-11-11T16:21:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Données invalides"),
     *                 @OA\Property(property="details", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pas de compte Orange Money",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="AUTH_006"),
     *                 @OA\Property(property="message", type="string", example="Ce numéro de téléphone n'a pas de compte Orange Money actif")
     *             )
     *         )
     *     )
     * )
     */
    // 1.1 Initier l'Inscription
    public function initierInscription(InitierInscriptionRequest $request)
    {
        $result = $this->authService->initierInscription($request->validated());
        return $this->responseFromResult($result);
    }


    // 1.1 Créer Compte (alias pour initier-inscription)
    public function creerCompte(InitierInscriptionRequest $request)
    {
        return $this->initierInscription($request);
    }

    /**
     * @OA\Post(
     *     path="/auth/verification-otp",
     *     summary="Vérifier le code OTP pour connexion",
     *     description="Valide le code OTP pour permettre la connexion d'un utilisateur existant",
     *     operationId="verificationOtp",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numeroTelephone","codeOTP"},
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771411251", description="Numéro de téléphone"),
     *             @OA\Property(property="codeOTP", type="string", example="123456", description="Code OTP à 6 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP validé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP validé"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="refreshToken", type="string", example="refresh_token_here"),
     *                 @OA\Property(property="expiresIn", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="OTP_INVALID"),
     *                 @OA\Property(property="message", type="string", example="Code OTP invalide ou expiré")
     *             )
     *         )
     *     )
     * )
     */
    // 1.2 Vérification OTP (pour connexion existante)
    public function verificationOtp(VerificationOtpRequest $request)
    {
        $result = $this->authService->verificationOtp($request->numeroTelephone, $request->codeOTP);
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/auth/connexion",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur avec son numéro de téléphone. Seuls les utilisateurs ayant un compte Orange Money peuvent se connecter. Un OTP est envoyé par SMS pour chaque connexion.",
     *     operationId="connexion",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numeroTelephone"},
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771411251", description="Numéro de téléphone"),
     *             @OA\Property(property="codeOTP", type="string", example="123456", description="Code OTP à 6 chiffres (requis pour finaliser la connexion)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé par SMS ou connexion réussie",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Code OTP envoyé par SMS. Veuillez vérifier votre téléphone."),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="numeroTelephone", type="string", example="+221771411251"),
     *                         @OA\Property(property="compteOrangeMoney", type="boolean", example=true),
     *                         @OA\Property(property="otpEnvoye", type="boolean", example=true),
     *                         @OA\Property(property="dateExpiration", type="string", format="date-time", example="2025-11-11T15:28:00Z")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Connexion réussie."),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="jetonAcces", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                         @OA\Property(property="jetonRafraichissement", type="string", example="refresh_token_here"),
     *                         @OA\Property(property="utilisateur", type="object",
     *                             @OA\Property(property="idUtilisateur", type="string", example="uuid"),
     *                             @OA\Property(property="numeroTelephone", type="string", example="+221771411251"),
     *                             @OA\Property(property="nomComplet", type="string", example="Moustapha Ndiaye"),
     *                             @OA\Property(property="email", type="string", example="moustapha.ndiaye@email.com"),
     *                             @OA\Property(property="statutKYC", type="string", example="verifie"),
     *                             @OA\Property(property="biometrieActivee", type="boolean", example=false),
     *                             @OA\Property(property="compteOrangeMoney", type="boolean", example=true),
     *                             @OA\Property(property="soldeOrangeMoney", type="number", example=50000)
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé ou pas de compte Orange Money",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="AUTH_006"),
     *                 @OA\Property(property="message", type="string", example="Ce numéro de téléphone n'a pas de compte Orange Money actif")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="OTP invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="AUTH_004"),
     *                 @OA\Property(property="message", type="string", example="OTP invalide ou expiré")
     *             )
     *         )
     *     )
     * )
     */
    // 1.3 Connexion
    public function connexion(ConnexionRequest $request)
    {
        $result = $this->authService->connexion($request->numeroTelephone, $request->codeOTP);
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/auth/rafraichir",
     *     summary="Rafraîchir le token d'accès",
     *     description="Génère un nouveau token d'accès à partir du refresh token",
     *     operationId="rafraichirToken",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"jetonRafraichissement"},
     *             @OA\Property(property="jetonRafraichissement", type="string", example="refresh_token_here", description="Token de rafraîchissement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="jetonAcces", type="string", example="dbBhNGIG5MuJWUJEV4mOjEcCq8nPNCXcH6jZMAnpSe0NsysKzOZCCiv30vyRYcvV"),
     *                 @OA\Property(property="jetonRafraichissement", type="string", example="YVX553dKWUKL6zGMWEPSMlQJDuFlC4KDOO0fnTxlfYOAO0T0TY0MFk9wyrX8x9If")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token de rafraîchissement invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="INVALID_REFRESH_TOKEN"),
     *                 @OA\Property(property="message", type="string", example="Token de rafraîchissement invalide")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 1.4 Rafraîchir le Token
    public function rafraichir(RafraichirTokenRequest $request)
    {
        $result = $this->authService->rafraichir($request->jetonRafraichissement);
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/auth/deconnexion",
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès actuel",
     *     operationId="deconnexion",
     *     tags={"Authentification"},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 1.5 Déconnexion
    public function deconnexion(Request $request)
    {
        $result = $this->authService->deconnexion();
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Get(
     *     path="/utilisateurs/profil",
     *     summary="Consulter le profil utilisateur",
     *     description="Récupère les informations du profil de l'utilisateur connecté",
     *     operationId="consulterProfil",
     *     tags={"Authentification"},
     *     @OA\Response(
     *         response=200,
     *         description="Profil récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="utilisateur", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="prenom", type="string", example="John"),
     *                     @OA\Property(property="nom", type="string", example="Doe"),
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771411251"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-11-10T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 1.6 Consulter Profil
    public function consulterProfil(Request $request)
    {
        $utilisateur = $request->user();
        $result = $this->userService->consulterProfil($utilisateur);
        return $this->responseFromResult($result);
    }

}
