<?php

namespace App\Services;

use App\Models\Utilisateur;
use App\Models\OrangeMoney;
use App\Models\Authentification;
use App\Models\QRCode;
use App\Traits\ServiceResponseTrait;
use App\Traits\DataFormattingTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

        // Récupérer les informations du compte Orange Money
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($data['numeroTelephone']);

        // Créer un enregistrement temporaire avec les informations de base
        // Utiliser les informations d'Orange Money si disponibles
        $utilisateurTemp = Utilisateur::create([
            'numero_telephone' => $data['numeroTelephone'],
            'prenom' => $compteOrangeMoney ? $compteOrangeMoney->prenom : $data['prenom'],
            'nom' => $compteOrangeMoney ? $compteOrangeMoney->nom : $data['nom'],
            'email' => $compteOrangeMoney ? $compteOrangeMoney->email : null,
            'numero_cni' => $compteOrangeMoney ? $compteOrangeMoney->numero_cni : null,
            'statut_kyc' => $compteOrangeMoney ? 'verifie' : 'en_attente_verification',
        ]);

        // Générer OTP
        $otp = $this->otpService->generateOtp();

        $utilisateurTemp->update([
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Ici, envoyer OTP par SMS (simulé)

        $message = $compteOrangeMoney
            ? 'Numéro Orange Money vérifié. OTP envoyé par SMS pour finaliser votre inscription.'
            : 'OTP envoyé par SMS. Veuillez saisir l\'OTP pour finaliser votre inscription.';

        return $this->successResponse([
            'idUtilisateur' => $utilisateurTemp->getKey(),
            'numeroTelephone' => $utilisateurTemp->numero_telephone,
            'compteOrangeMoney' => $compteOrangeMoney ? true : false,
            'otpEnvoye' => true,
            'dateExpiration' => optional($utilisateurTemp->otp_expires_at)?->toIso8601String(),
        ], $message, 201);
    }

    /**
     * Finaliser l'inscription
     *
     * @param string $numeroTelephone
     * @param string $codeOTP
     * @param array|null $dataSupplementaires
     * @return array
     */
    public function finaliserInscription($numeroTelephone, $codeOTP, $dataSupplementaires = null)
    {
        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$utilisateur) {
            return $this->errorResponse('AUTH_005', 'Utilisateur non trouvé', [], 404);
        }

        // Vérifier si c'est un compte Orange Money existant
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($numeroTelephone);

        if ($compteOrangeMoney && $utilisateur->statut_kyc === 'verifie') {
            // Compte Orange Money existant - connexion directe
            if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
                return $this->errorResponse('AUTH_004', 'OTP invalide ou expiré', [], 401);
            }

            // Mettre à jour la dernière connexion Orange Money
            $compteOrangeMoney->mettreAJourConnexion();

            // Générer tokens
            $tokens = $this->tokenService->generateTokens($utilisateur);

            return $this->successResponse([
                'jetonAcces' => $tokens['accessToken'],
                'jetonRafraichissement' => $tokens['refreshToken'],
                'utilisateur' => [
                    'idUtilisateur' => $utilisateur->getKey(),
                    'numeroTelephone' => $utilisateur->numero_telephone,
                    'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                    'email' => $utilisateur->email,
                    'compteOrangeMoney' => true,
                    'soldeOrangeMoney' => $compteOrangeMoney->solde,
                    'idPortefeuille' => optional($utilisateur->portefeuille)->getKey(),
                ]
            ], 'Connexion réussie avec votre compte Orange Money');
        }

        // Nouveau compte - vérifier le statut en attente
        if ($utilisateur->statut_kyc !== 'en_attente_verification') {
            return $this->errorResponse('AUTH_005', 'Utilisateur déjà inscrit', [], 409);
        }

        if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
            return $this->errorResponse('AUTH_004', 'OTP invalide ou expiré', [], 401);
        }

        // Mettre à jour l'utilisateur avec les informations complètes
        $utilisateur->update([
            'email' => $dataSupplementaires['email'],
            'code_pin' => Hash::make($dataSupplementaires['codePin']),
            'numero_cni' => $dataSupplementaires['numeroCNI'],
            'statut_kyc' => 'verifie',
            'otp' => null, // Supprimer l'OTP après utilisation
            'otp_expires_at' => null,
        ]);

        // Générer un QR code pour le compte utilisateur
        $qrCode = QRCode::create([
            'id_marchand' => null, // QR code pour compte utilisateur, pas pour marchand
            'id_utilisateur' => $utilisateur->getKey(),
            'donnees' => json_encode([
                'type' => 'compte_utilisateur',
                'idUtilisateur' => $utilisateur->getKey(),
                'numeroTelephone' => $utilisateur->numero_telephone,
                'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
            ]),
            'montant' => null, // Pas de montant pour un QR code de compte
            'date_expiration' => Carbon::now()->addYears(10), // QR code valide longtemps
            'utilise' => false,
        ]);

        // Générer tokens
        $tokens = $this->tokenService->generateTokens($utilisateur);

        return $this->successResponse([
            'jetonAcces' => $tokens['accessToken'],
            'jetonRafraichissement' => $tokens['refreshToken'],
            'utilisateur' => [
                'idUtilisateur' => $utilisateur->getKey(),
                'numeroTelephone' => $utilisateur->numero_telephone,
                'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                'email' => $utilisateur->email,
                'idPortefeuille' => optional($utilisateur->portefeuille)->getKey(),
            ],
            'qrCode' => [
                'idQRCode' => $qrCode->id,
                'donnees' => $qrCode->donnees,
                'dateGeneration' => $qrCode->date_generation->toISOString(),
                'dateExpiration' => $qrCode->date_expiration->toISOString(),
            ]
        ], 'Inscription finalisée avec succès. QR code généré pour votre compte.', 201);
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
        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
            return $this->errorResponse('AUTH_004', 'OTP invalide ou expiré', [], 401);
        }

        // Générer tokens
        $tokens = $this->tokenService->generateTokens($utilisateur);

        $utilisateur->update(['statut_kyc' => 'verifie']);

        return $this->successResponse([
            'jetonAcces' => $tokens['accessToken'],
            'jetonRafraichissement' => $tokens['refreshToken'],
            'utilisateur' => [
                'idUtilisateur' => $utilisateur->getKey(),
                'numeroTelephone' => $utilisateur->numero_telephone,
                'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                'idPortefeuille' => optional($utilisateur->portefeuille)->getKey(),
            ]
        ], 'Authentification réussie');
    }

    /**
     * Connexion utilisateur
     *
     * @param string $numeroTelephone
     * @param string $codePin
     * @return array
     */
    public function connexion($numeroTelephone, $codePin)
    {
        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$utilisateur || !Hash::check($codePin, $utilisateur->code_pin)) {
            return $this->errorResponse('AUTH_001', 'Identifiants invalides', [], 401);
        }

        if ($utilisateur->statut_kyc !== 'verifie') {
            return $this->errorResponse('AUTH_002', 'Compte non vérifié', [], 401);
        }

        // Mettre à jour la dernière connexion Orange Money si applicable
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($numeroTelephone);
        if ($compteOrangeMoney) {
            $compteOrangeMoney->mettreAJourConnexion();
        }

        // Générer tokens
        $tokens = $this->tokenService->generateTokens($utilisateur);

        return $this->successResponse([
            'jetonAcces' => $tokens['accessToken'],
            'jetonRafraichissement' => $tokens['refreshToken'],
            'utilisateur' => [
                'idUtilisateur' => $utilisateur->getKey(),
                'numeroTelephone' => $utilisateur->numero_telephone,
                'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                'email' => $utilisateur->email,
                'statutKYC' => $utilisateur->statut_kyc,
                'biometrieActivee' => $utilisateur->biometrie_activee,
                'compteOrangeMoney' => $compteOrangeMoney ? true : false,
                'soldeOrangeMoney' => $compteOrangeMoney ? $compteOrangeMoney->solde : null,
            ]
        ], 'Connexion réussie');
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