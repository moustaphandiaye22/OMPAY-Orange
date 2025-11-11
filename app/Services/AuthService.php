<?php

namespace App\Services;

use App\Models\Utilisateur;
use App\Models\OrangeMoney;
use App\Models\Authentification;
use App\Models\QRCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    protected $tokenService;
    protected $otpService;

    public function __construct(TokenService $tokenService, OtpService $otpService)
    {
        $this->tokenService = $tokenService;
        $this->otpService = $otpService;
    }

    // 1.1 Initier l'Inscription
    public function initierInscription($data)
    {
        // Vérifier si l'utilisateur existe déjà dans notre système
        $utilisateurExistant = Utilisateur::where('numero_telephone', $data['numeroTelephone'])->first();

        if ($utilisateurExistant) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_003',
                    'message' => 'Numéro de téléphone déjà utilisé'
                ],
                'status' => 409
            ];
        }

        // Vérifier si le numéro a un compte Orange Money
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($data['numeroTelephone']);

        if (!$compteOrangeMoney) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_006',
                    'message' => 'Ce numéro de téléphone n\'a pas de compte Orange Money actif'
                ],
                'status' => 404
            ];
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

        return [
            'success' => true,
            'data' => [
                'idUtilisateur' => $utilisateurTemp->getKey(),
                'numeroTelephone' => $utilisateurTemp->numero_telephone,
                'compteOrangeMoney' => $compteOrangeMoney ? true : false,
                'otpEnvoye' => true,
                'dateExpiration' => optional($utilisateurTemp->otp_expires_at)?->toIso8601String(),
            ],
            'message' => $message,
            'status' => 200
        ];
    }

    // 1.1.1 Finaliser l'Inscription
    public function finaliserInscription($numeroTelephone, $codeOTP, $dataSupplementaires = null)
    {
        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$utilisateur) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_005',
                    'message' => 'Utilisateur non trouvé'
                ],
                'status' => 404
            ];
        }

        // Vérifier si c'est un compte Orange Money existant
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($numeroTelephone);

        if ($compteOrangeMoney && $utilisateur->statut_kyc === 'verifie') {
            // Compte Orange Money existant - connexion directe
            if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'AUTH_004',
                        'message' => 'OTP invalide ou expiré'
                    ],
                    'status' => 401
                ];
            }

            // Mettre à jour la dernière connexion Orange Money
            $compteOrangeMoney->mettreAJourConnexion();

            // Générer tokens
            $tokens = $this->tokenService->generateTokens($utilisateur);

            return [
                'success' => true,
                'data' => [
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
                ],
                'message' => 'Connexion réussie avec votre compte Orange Money',
                'status' => 200
            ];
        }

        // Nouveau compte - vérifier le statut en attente
        if ($utilisateur->statut_kyc !== 'en_attente_verification') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_005',
                    'message' => 'Utilisateur déjà inscrit'
                ],
                'status' => 409
            ];
        }

        if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_004',
                    'message' => 'OTP invalide ou expiré'
                ],
                'status' => 401
            ];
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

        return [
            'success' => true,
            'data' => [
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
            ],
            'message' => 'Inscription finalisée avec succès. QR code généré pour votre compte.',
            'status' => 201
        ];
    }

    // 1.2 Vérification OTP
    public function verificationOtp($numeroTelephone, $codeOTP)
    {
        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$this->otpService->verifyOtp($utilisateur, $codeOTP)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_004',
                    'message' => 'OTP invalide ou expiré'
                ],
                'status' => 401
            ];
        }

        // Générer tokens
        $tokens = $this->tokenService->generateTokens($utilisateur);

        $utilisateur->update(['statut_kyc' => 'verifie']);

        return [
            'success' => true,
            'data' => [
                'jetonAcces' => $tokens['accessToken'],
                'jetonRafraichissement' => $tokens['refreshToken'],
                'utilisateur' => [
                    'idUtilisateur' => $utilisateur->getKey(),
                    'numeroTelephone' => $utilisateur->numero_telephone,
                    'nomComplet' => $utilisateur->prenom . ' ' . $utilisateur->nom,
                    'idPortefeuille' => optional($utilisateur->portefeuille)->getKey(),
                ]
            ],
            'message' => 'Authentification réussie'
        ];
    }

    // 1.3 Connexion
    public function connexion($numeroTelephone, $codePin)
    {
        $utilisateur = Utilisateur::where('numero_telephone', $numeroTelephone)->first();

        if (!$utilisateur || !Hash::check($codePin, $utilisateur->code_pin)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_001',
                    'message' => 'Identifiants invalides'
                ],
                'status' => 401
            ];
        }

        if ($utilisateur->statut_kyc !== 'verifie') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'AUTH_002',
                    'message' => 'Compte non vérifié'
                ],
                'status' => 401
            ];
        }

        // Mettre à jour la dernière connexion Orange Money si applicable
        $compteOrangeMoney = OrangeMoney::verifierExistenceCompte($numeroTelephone);
        if ($compteOrangeMoney) {
            $compteOrangeMoney->mettreAJourConnexion();
        }

        // Générer tokens
        $tokens = $this->tokenService->generateTokens($utilisateur);

        return [
            'success' => true,
            'data' => [
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
            ],
            'message' => 'Connexion réussie'
        ];
    }

    // 1.4 Rafraîchir le Token
    public function rafraichir($jetonRafraichissement)
    {
        $result = $this->tokenService->refreshTokens($jetonRafraichissement);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'data' => [
                'jetonAcces' => $result['data']['accessToken'],
                'jetonRafraichissement' => $result['data']['refreshToken'],
            ]
        ];
    }

    // 1.5 Déconnexion
    public function deconnexion()
    {
        // Supprimer le token d'accès (simulé)
        // Dans un vrai système, invalider le token

        return [
            'success' => true,
            'message' => 'Déconnexion réussie'
        ];
    }
}