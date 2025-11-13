<?php

namespace App\Services;

use App\Models\Utilisateur;
use App\Models\OrangeMoney;
use App\Models\Authentification;
use App\Models\QRCode;
use App\Traits\ServiceResponseTrait;
use App\Traits\DataFormattingTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\TokenService;
use App\Services\OtpService;

class AuthService
{
    use ServiceResponseTrait, DataFormattingTrait;

    protected $tokenService;
    protected $otpService;

    public function __construct(TokenService $tokenService, OtpService $otpService)
    {
        $this->tokenService = $tokenService;
        $this->otpService = $otpService;
    }

    /**
     * Initier l'inscription d'un utilisateur
     *
     * @param array $data
     * @return array
     */
    public function initierInscription($data)
    {
        Log::info('AuthService::initierInscription called', ['numeroTelephone' => $data['numeroTelephone'] ?? 'missing']);

        // Vérifier si l'utilisateur existe déjà dans notre système
        $utilisateurExistant = Utilisateur::where('numero_telephone', $data['numeroTelephone'])->first();

        if ($utilisateurExistant) {
            return $this->errorResponse('AUTH_003', 'Numéro de téléphone déjà utilisé', [], 409);
        }

        // Vérifier si le numéro a un compte Orange Money
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($data['numeroTelephone']);

        if (!$compteOrangeMoney) {
            return $this->errorResponse('AUTH_006', 'Ce numéro de téléphone n\'a pas de compte Orange Money actif', [], 404);
        }

        // Créer l'utilisateur complet avec toutes les informations
        $utilisateur = Utilisateur::create([
            'numero_telephone' => $data['numeroTelephone'],
            'prenom' => $data['prenom'] ?? $compteOrangeMoney->prenom,
            'nom' => $data['nom'] ?? $compteOrangeMoney->nom,
            'email' => $data['email'] ?? $compteOrangeMoney->email,
            'numero_cni' => $data['numeroCNI'] ?? $compteOrangeMoney->numero_cni,
            'statut_kyc' => 'en_attente_verification', // Statut pour première connexion
        ]);

        // Créer le portefeuille
        $portefeuille = $utilisateur->portefeuille()->create([
            'solde' => 0,
            'devise' => 'FCFA',
        ]);

        // Créer les paramètres de sécurité
        $utilisateur->parametresSecurite()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'biometrie_active' => false,
            'tentatives_echouees' => 0,
        ]);

        // Créer le QR code personnel de l'utilisateur (pour recevoir des paiements)
        $qrCodeUtilisateur = $utilisateur->qrCodes()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'donnees' => '', // Sera généré lors de l'appel à generer()
            'date_generation' => Carbon::now(),
            'date_expiration' => Carbon::now()->addYears(10), // QR code permanent (10 ans)
            'utilise' => false, // Les QR utilisateur ne sont jamais "utilisés" de la même façon
        ]);

        // Générer les données du QR code
        $qrCodeUtilisateur->update([
            'donnees' => $qrCodeUtilisateur->generer()
        ]);

        // Générer OTP pour activation du compte
        $otp = $this->otpService->generateOtp();

        $utilisateur->update([
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Envoyer OTP par SMS
        $smsSent = $this->otpService->sendOtpSms($data['numeroTelephone'], $otp);

        if (!$smsSent) {
            return $this->errorResponse('AUTH_007', 'Erreur lors de l\'envoi du SMS OTP', [], 500);
        }

        return $this->successResponse([
            'idUtilisateur' => $utilisateur->getKey(),
            'numeroTelephone' => $utilisateur->numero_telephone,
            'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
            'compteOrangeMoney' => true,
            'otpEnvoye' => true,
            'dateExpiration' => $utilisateur->otp_expires_at->toIso8601String(),
        ], 'Compte créé avec succès. Code OTP envoyé par SMS pour activation lors de votre première connexion.', 201);
    }


    /**
     * Vérification OTP
     *
     * @param string $numeroTelephone
     * @param string $codeOTP
     * @return array
     */
    public function verificationOtp($numeroTelephone, $codeOTP)
    {
        Log::info('AuthService::verificationOtp called', ['numeroTelephone' => $numeroTelephone]);

        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$utilisateur || !$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
            return $this->errorResponse('AUTH_004', 'OTP invalide ou expiré', [], 401);
        }

        // Invalider l'OTP après utilisation
        $this->otpService->invalidateOtp($utilisateur);

        // Mettre à jour la dernière connexion Orange Money si applicable
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($numeroTelephone);
        if ($compteOrangeMoney) {
            $compteOrangeMoney->mettreAJourConnexion();
        }

        // Générer tokens
        $tokens = $this->tokenService->generateTokens($utilisateur);

        $responseData = [
            'jetonAcces' => $tokens['accessToken'],
            'jetonRafraichissement' => $tokens['refreshToken']
        ];

        Log::info('OTP Verification Success Response', [
            'numeroTelephone' => $numeroTelephone,
            'hasUserInfo' => false,
            'userInfoRemoved' => true
        ]);

        return $this->successResponse($responseData, 'Connexion réussie');
    }

    /**
     * Connexion utilisateur
     *
     * @param string $numeroTelephone
     * @param string|null $codeOTP
     * @return array
     */
    public function connexion($numeroTelephone, $codeOTP = null)
    {
        Log::info('AuthService::connexion called', ['numeroTelephone' => $numeroTelephone, 'hasCodeOTP' => !is_null($codeOTP)]);

        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$utilisateur) {
            return $this->errorResponse('AUTH_001', 'Utilisateur non trouvé', [], 404);
        }

        // Vérifier si l'utilisateur a un compte Orange Money
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($numeroTelephone);

        if (!$compteOrangeMoney) {
            return $this->errorResponse('AUTH_006', 'Ce numéro de téléphone n\'a pas de compte Orange Money actif', [], 404);
        }

        // Si le compte est en attente de vérification (première connexion)
        if ($utilisateur->statut_kyc === 'en_attente_verification') {
            if (!$codeOTP) {
                // Première connexion - envoyer OTP pour vérification
                $otp = $this->otpService->generateOtp();

                $utilisateur->update([
                    'otp' => $otp,
                    'otp_expires_at' => Carbon::now()->addMinutes(5),
                ]);

                // Envoyer OTP par SMS
                $smsSent = $this->otpService->sendOtpSms($numeroTelephone, $otp);

                if (!$smsSent) {
                    return $this->errorResponse('AUTH_007', 'Erreur lors de l\'envoi du SMS OTP', [], 500);
                }

                return $this->successResponse([
                    'numeroTelephone' => $numeroTelephone,
                    'compteOrangeMoney' => true,
                    'premiereConnexion' => true,
                    'otpEnvoye' => true,
                    'dateExpiration' => $utilisateur->otp_expires_at->toIso8601String(),
                ], 'Première connexion détectée. Code OTP envoyé par SMS pour vérifier votre compte.');
            } else {
                // Vérifier OTP pour activer le compte
                if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
                    return $this->errorResponse('AUTH_004', 'OTP invalide ou expiré', [], 401);
                }

                // Activer le compte
                $utilisateur->update([
                    'statut_kyc' => 'verifie',
                    'otp' => null,
                    'otp_expires_at' => null,
                ]);

                // Mettre à jour la dernière connexion Orange Money
                $compteOrangeMoney->mettreAJourConnexion();

                // Générer tokens
                $tokens = $this->tokenService->generateTokens($utilisateur);

                return $this->successResponse([
                    'jetonAcces' => $tokens['accessToken'],
                    'jetonRafraichissement' => $tokens['refreshToken']
                ], 'Compte vérifié avec succès. Connexion réussie.');
            }
        }

        // Pour les comptes déjà activés, toujours envoyer OTP pour chaque connexion
        if ($utilisateur->statut_kyc !== 'verifie') {
            return $this->errorResponse('AUTH_002', 'Compte non activé', [], 401);
        }

        if (!$codeOTP) {
            // Envoyer OTP par SMS pour connexion
            $otp = $this->otpService->generateOtp();

            $utilisateur->update([
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(5),
            ]);

            // Envoyer OTP par SMS
            $smsSent = $this->otpService->sendOtpSms($numeroTelephone, $otp);

            if (!$smsSent) {
                return $this->errorResponse('AUTH_007', 'Erreur lors de l\'envoi du SMS OTP', [], 500);
            }

            return $this->successResponse([
                'numeroTelephone' => $numeroTelephone,
                'compteOrangeMoney' => true,
                'otpEnvoye' => true,
                'dateExpiration' => $utilisateur->otp_expires_at->toIso8601String(),
            ], 'Code OTP envoyé par SMS. Veuillez vérifier votre téléphone.');
        } else {
            // Vérifier OTP pour connexion
            if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
                return $this->errorResponse('AUTH_004', 'OTP invalide ou expiré', [], 401);
            }

            // Invalider l'OTP après utilisation
            $this->otpService->invalidateOtp($utilisateur);

            // Mettre à jour la dernière connexion Orange Money
            $compteOrangeMoney->mettreAJourConnexion();

            // Générer tokens
            $tokens = $this->tokenService->generateTokens($utilisateur);

            return $this->successResponse([
                'jetonAcces' => $tokens['accessToken'],
                'jetonRafraichissement' => $tokens['refreshToken']
            ], 'Connexion réussie.');
        }
    }

    /**
     * Rafraîchir le token
     *
     * @param string $jetonRafraichissement
     * @return array
     */
    public function rafraichir($jetonRafraichissement)
    {
        $result = $this->tokenService->refreshTokens($jetonRafraichissement);

        if (!$result['success']) {
            return $result;
        }

        return $this->successResponse([
            'jetonAcces' => $result['data']['accessToken'],
            'jetonRafraichissement' => $result['data']['refreshToken'],
        ]);
    }

    /**
     * Déconnexion utilisateur
     *
     * @return array
     */
    public function deconnexion()
    {
        // Supprimer le token d'accès (simulé)
        // Dans un vrai système, invalider le token

        return $this->successResponse(null, 'Déconnexion réussie');
    }
}