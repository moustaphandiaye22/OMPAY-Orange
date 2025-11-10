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
use App\Http\Requests\MettreAJourProfilRequest;
use App\Http\Requests\ChangerPinRequest;
use App\Http\Requests\ActiverBiometrieRequest;
use Illuminate\Http\Request;

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

    // 1.1 Initier l'Inscription
    public function initierInscription(InitierInscriptionRequest $request)
    {
        $result = $this->authService->initierInscription($request->validated());
        return $this->responseFromResult($result);
    }

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

    // 1.2 Vérification OTP (pour connexion existante)
    public function verificationOtp(VerificationOtpRequest $request)
    {
        $result = $this->authService->verificationOtp($request->numeroTelephone, $request->codeOTP);
        return $this->responseFromResult($result);
    }

    // 1.3 Connexion
    public function connexion(ConnexionRequest $request)
    {
        $result = $this->authService->connexion($request->numeroTelephone, $request->codePin);
        return $this->responseFromResult($result);
    }

    // 1.4 Rafraîchir le Token
    public function rafraichir(RafraichirTokenRequest $request)
    {
        $result = $this->authService->rafraichir($request->jetonRafraichissement);
        return $this->responseFromResult($result);
    }

    // 1.5 Déconnexion
    public function deconnexion(Request $request)
    {
        $result = $this->authService->deconnexion();
        return $this->responseFromResult($result);
    }

    // 1.6 Consulter Profil
    public function consulterProfil(Request $request)
    {
        $utilisateur = $request->user();
        $result = $this->userService->consulterProfil($utilisateur);
        return $this->responseFromResult($result);
    }

    // 1.7 Mettre à jour Profil
    public function mettreAJourProfil(MettreAJourProfilRequest $request)
    {
        $utilisateur = $request->user();

        // Vérifier le PIN avant mise à jour
        if (!$this->securityService->verifierPin($utilisateur, $request->codePin)) {
            return $this->errorResponse('USER_006', 'PIN incorrect', [], 401);
        }

        $result = $this->userService->mettreAJourProfil($utilisateur, $request->only(['prenom', 'nom', 'email']));
        return $this->responseFromResult($result);
    }

    // 1.8 Changer le Code PIN
    public function changerPin(ChangerPinRequest $request)
    {
        $utilisateur = $request->user();
        $result = $this->securityService->changerPin($utilisateur, $request->ancienPin, $request->nouveauPin);
        return $this->responseFromResult($result);
    }

    // 1.9 Activer la Biométrie
    public function activerBiometrie(ActiverBiometrieRequest $request)
    {
        $utilisateur = $request->user();
        $result = $this->securityService->activerBiometrie($utilisateur, $request->codePin, $request->jetonBiometrique);
        return $this->responseFromResult($result);
    }
}
