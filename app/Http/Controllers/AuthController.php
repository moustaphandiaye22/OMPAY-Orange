<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\UserService;
use App\Services\SecurityService;
use App\Http\Requests\InitierInscriptionRequest;
use App\Http\Requests\FinaliserInscriptionRequest;
use App\Http\Requests\VerificationOtpRequest;
use App\Http\Requests\ConnexionRequest;
use App\Http\Requests\RafraichirTokenRequest;
use App\Http\Requests\ChangerPinRequest;
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
     *     summary="Initier l'inscription d'un nouvel utilisateur",
     *     description="Crée un compte utilisateur en envoyant un OTP pour vérification",
     *     operationId="initierInscription",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prenom","nom","numeroTelephone"},
     *             @OA\Property(property="prenom", type="string", example="John", description="Prénom de l'utilisateur"),
     *             @OA\Property(property="nom", type="string", example="Doe", description="Nom de l'utilisateur"),
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771234567", description="Numéro de téléphone au format +221XXXXXXXXX")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="idUtilisateur", type="string", example="uuid"),
     *                 @OA\Property(property="numeroTelephone", type="string", example="+221771234567")
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
     *     )
     * )
     */
    // 1.1 Initier l'Inscription
    public function initierInscription(InitierInscriptionRequest $request)
    {
        $result = $this->authService->initierInscription($request->validated());
        return $this->responseFromResult($result);
    }

    /**
     * @OA\Post(
     *     path="/auth/finaliser-inscription",
     *     summary="Finaliser l'inscription avec le code OTP",
     *     description="Valide le code OTP et complète l'inscription de l'utilisateur",
     *     operationId="finaliserInscription",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numeroTelephone","codeOTP","email","codePin","numeroCNI"},
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="codeOTP", type="string", example="123456", description="Code OTP à 6 chiffres"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Adresse email"),
     *             @OA\Property(property="codePin", type="string", example="1234", description="Code PIN à 4 chiffres"),
     *             @OA\Property(property="numeroCNI", type="string", example="1234567890123", description="Numéro CNI à 13 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inscription finalisée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inscription finalisée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="utilisateur", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="prenom", type="string", example="John"),
     *                     @OA\Property(property="nom", type="string", example="Doe"),
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com")
     *                 ),
     *                 @OA\Property(property="portefeuille", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="solde", type="number", example=0),
     *                     @OA\Property(property="devise", type="string", example="XOF")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré",
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
    // 1.1.1 Finaliser l'Inscription
    public function finaliserInscription(FinaliserInscriptionRequest $request)
    {
        $result = $this->authService->finaliserInscription(
            $request->numeroTelephone,
            $request->codeOTP,
            [
                'email' => $request->email,
                'codePin' => $request->codePin,
                'numeroCNI' => $request->numeroCNI,
            ]
        );
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
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771234567", description="Numéro de téléphone"),
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
     *     description="Authentifie un utilisateur avec son numéro de téléphone et code PIN",
     *     operationId="connexion",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numeroTelephone","codePin"},
     *             @OA\Property(property="numeroTelephone", type="string", example="+221771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="codePin", type="string", example="1234", description="Code PIN à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="jetonAcces", type="string", example="dbBhNGIG5MuJWUJEV4mOjEcCq8nPNCXcH6jZMAnpSe0NsysKzOZCCiv30vyRYcvV", description="Token d'accès à utiliser pour les requêtes authentifiées"),
     *                 @OA\Property(property="jetonRafraichissement", type="string", example="YVX553dKWUKL6zGMWEPSMlQJDuFlC4KDOO0fnTxlfYOAO0T0TY0MFk9wyrX8x9If", description="Token de rafraîchissement pour obtenir un nouveau token"),
     *                 @OA\Property(property="utilisateur", type="object",
     *                     @OA\Property(property="idUtilisateur", type="string", example="a053e8c2-225f-4c83-ad2d-610cfec446c7"),
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771000001"),
     *                     @OA\Property(property="nomComplet", type="string", example="Tmp User"),
     *                     @OA\Property(property="email", type="string", example="tmp.user@example.com"),
     *                     @OA\Property(property="statutKYC", type="string", example="verifie"),
     *                     @OA\Property(property="biometrieActivee", type="boolean", example=false),
     *                     @OA\Property(property="compteOrangeMoney", type="boolean", example=false),
     *                     @OA\Property(property="soldeOrangeMoney", type="string", example=null)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="INVALID_CREDENTIALS"),
     *                 @OA\Property(property="message", type="string", example="Numéro de téléphone ou code PIN incorrect")
     *             )
     *         )
     *     )
     * )
     */
    // 1.3 Connexion
    public function connexion(ConnexionRequest $request)
    {
        $result = $this->authService->connexion($request->numeroTelephone, $request->codePin);
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
     *                     @OA\Property(property="numeroTelephone", type="string", example="+221771234567"),
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

    /**
     * @OA\Post(
     *     path="/utilisateurs/changer-pin",
     *     summary="Changer le code PIN",
     *     description="Modifie le code PIN de l'utilisateur après vérification de l'ancien PIN",
     *     operationId="changerPin",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ancienPin","nouveauPin","confirmationPin"},
     *             @OA\Property(property="ancienPin", type="string", example="1234", description="Ancien code PIN"),
     *             @OA\Property(property="nouveauPin", type="string", example="5678", description="Nouveau code PIN"),
     *             @OA\Property(property="confirmationPin", type="string", example="5678", description="Confirmation du nouveau code PIN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code PIN changé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code PIN changé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ancien PIN incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="INVALID_OLD_PIN"),
     *                 @OA\Property(property="message", type="string", example="L'ancien code PIN est incorrect")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    // 1.7 Changer le Code PIN
    public function changerPin(ChangerPinRequest $request)
    {
        $utilisateur = $request->user();
        $result = $this->securityService->changerPin($utilisateur, $request->ancienPin, $request->nouveauPin);
        return $this->responseFromResult($result);
    }
}
