<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortefeuilleController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HistoriqueController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentification & Utilisateur
Route::prefix('auth')->group(function () {
    Route::post('inscription', [AuthController::class, 'inscription']);
    Route::post('verification-otp', [AuthController::class, 'verificationOtp']);
    Route::post('connexion', [AuthController::class, 'connexion']);
    Route::post('rafraichir', [AuthController::class, 'rafraichir']);
    Route::post('deconnexion', [AuthController::class, 'deconnexion'])->middleware('auth.token');
});

Route::middleware(['auth.token', 'rate.limit'])->group(function () {
    // Utilisateur
    Route::prefix('utilisateurs')->group(function () {
        Route::get('profil', [AuthController::class, 'consulterProfil']);
        Route::put('profil', [AuthController::class, 'mettreAJourProfil']);
        Route::post('changer-pin', [AuthController::class, 'changerPin']);
        Route::post('activer-biometrie', [AuthController::class, 'activerBiometrie']);
    });

    // Portefeuille
    Route::prefix('portefeuille')->group(function () {
        Route::get('solde', [PortefeuilleController::class, 'consulterSolde']);
        Route::get('transactions', [PortefeuilleController::class, 'historiqueTransactions']);
        Route::get('transactions/{idTransaction}', [PortefeuilleController::class, 'detailsTransaction']);
    });

    // Transferts
    Route::prefix('transferts')->group(function () {
        Route::post('verifier-destinataire', [TransfertController::class, 'verifierDestinataire']);
        Route::post('initier', [TransfertController::class, 'initierTransfert']);
        Route::post('{idTransfert}/confirmer', [TransfertController::class, 'confirmerTransfert']);
        Route::delete('{idTransfert}', [TransfertController::class, 'annulerTransfert']);
    });

    // Paiements Marchands
    Route::prefix('paiements')->group(function () {
        Route::get('categories', [PaiementController::class, 'listerCategories']);
        Route::post('scanner-qr', [PaiementController::class, 'scannerQR']);
        Route::post('saisir-code', [PaiementController::class, 'saisirCode']);
        Route::post('initier', [PaiementController::class, 'initierPaiement']);
        Route::post('{idPaiement}/confirmer', [PaiementController::class, 'confirmerPaiement']);
        Route::delete('{idPaiement}', [PaiementController::class, 'annulerPaiement']);
    });

    // Contacts
    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'listerContacts']);
        Route::post('/', [ContactController::class, 'ajouterContact']);
    });

    // Historique
    Route::prefix('historique')->group(function () {
        Route::get('rechercher', [HistoriqueController::class, 'rechercher']);
    });
});

Route::middleware('auth.token')->get('/user', function (Request $request) {
    return $request->user();
});
