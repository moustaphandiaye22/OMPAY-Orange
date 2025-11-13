<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortefeuilleController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\PaiementController;

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
    Route::post('creercompte', [AuthController::class, 'creerCompte']);
    Route::post('finaliser-inscription', [AuthController::class, 'finaliserInscription']);
    Route::post('verification-otp', [AuthController::class, 'verificationOtp']);
    Route::post('connexion', [AuthController::class, 'connexion']);
    Route::post('rafraichir', [AuthController::class, 'rafraichir']);
    Route::post('deconnexion', [AuthController::class, 'deconnexion'])->middleware('auth.token');
});

Route::middleware(['auth.token', 'rate.limit'])->group(function () {
    // Compte Dashboard
    Route::get('compte', [AuthController::class, 'compte']);

    // Utilisateur
    Route::prefix('utilisateurs')->group(function () {
        Route::get('profil', [AuthController::class, 'consulterProfil']);
        Route::post('changer-pin', [AuthController::class, 'changerPin']);
    });

    // Portefeuille
    Route::prefix('portefeuille')->group(function () {
        Route::post('{id}/solde', [PortefeuilleController::class, 'consulterSolde']);
        Route::post('{id}/transactions', [PortefeuilleController::class, 'historiqueTransactions']);
        Route::post('{id}/transactions/{idTransaction}', [PortefeuilleController::class, 'detailsTransaction']);
    });

    // Transferts
    Route::prefix('transfert')->group(function () {
        Route::post('effectuer-transfert', [TransfertController::class, 'effectuerTransfert']);
        Route::delete('{id}/annuler-transfert', [TransfertController::class, 'annulerTransfert']);
    });

    // Paiements Marchands
    Route::prefix('paiement')->group(function () {
        Route::post('effectuer-paiement', [PaiementController::class, 'effectuerPaiement']);
    });

});

Route::middleware('auth.token')->get('/user', function (Request $request) {
    return $request->user();
});
