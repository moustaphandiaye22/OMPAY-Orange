import 'dart:io';
import '../features/auth/auth_service_interface.dart';
import '../features/wallet/wallet_service_interface.dart';
import '../features/transfer/transfer_service_interface.dart';
import '../features/payment/payment_service_interface.dart';
import '../views/console_views.dart';
import '../models/models.dart';
import '../cache/cache_manager.dart';

class ConsoleService {
  final AuthServiceInterface _authService;
  final WalletServiceInterface _walletService;
  final TransferServiceInterface _transferService;
  final PaymentServiceInterface _paymentService;

  ConsoleService(this._authService, this._walletService, this._transferService, this._paymentService);

  Future<void> registerAccount() async {
    ConsoleViews.displaySection('Créer un Nouveau Compte');
    ConsoleViews.displayInputPrompt('Prénom');
    String? prenom = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Nom');
    String? nom = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Numéro de téléphone (+221XXXXXXXXX)');
    String? telephone = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Email');
    String? email = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Numéro CNI (13 chiffres)');
    String? cni = stdin.readLineSync();

    if (prenom == null || nom == null || telephone == null || email == null || cni == null) {
      ConsoleViews.displayError('Tous les champs sont obligatoires.');
      return;
    }

    try {
      final request = RegisterRequest(
        prenom: prenom,
        nom: nom,
        numeroTelephone: telephone,
        email: email,
        numeroCni: cni,
      );
      final result = await _authService.creerCompte(request);
      _handleAuthResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> finalizeRegistration() async {
    ConsoleViews.displaySection('Finaliser l\'Inscription');
    ConsoleViews.displayInputPrompt('Numéro de téléphone');
    String? telephone = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Code OTP');
    String? otp = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Email');
    String? email = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('PIN (4 chiffres)');
    String? pin = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Numéro CNI');
    String? cni = stdin.readLineSync();

    if (telephone == null || otp == null || email == null || pin == null || cni == null) {
      ConsoleViews.displayError('Tous les champs sont obligatoires.');
      return;
    }

    try {
      final request = FinalizeRegistrationRequest(
        numeroTelephone: telephone,
        codeOtp: otp,
        email: email,
        codePin: pin,
        numeroCni: cni,
      );
      final result = await _authService.finaliserInscription(request);
      _handleAuthResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> login() async {
    ConsoleViews.displaySection('Connexion');
    ConsoleViews.displayInputPrompt('Numéro de téléphone');
    String? telephone = stdin.readLineSync();

    if (telephone == null) {
      ConsoleViews.displayError('Le numéro de téléphone est obligatoire.');
      return;
    }

    try {
      // First, request OTP
      final otpRequest = LoginRequest(numeroTelephone: telephone);
      final otpResult = await _authService.connexion(otpRequest);
      if (otpResult.success && otpResult.data?['otpEnvoye'] == true) {
        ConsoleViews.displayInfo('OTP envoyé sur votre téléphone. Veuillez le saisir:');
        ConsoleViews.displayInputPrompt('Code OTP');
        String? otp = stdin.readLineSync();
        if (otp == null) return;

        // Now login with OTP
        final loginRequest = LoginRequest(numeroTelephone: telephone, codeOtp: otp);
        final loginResult = await _authService.connexion(loginRequest);
        if (loginResult.success) {
          ConsoleViews.displaySuccess('Connexion réussie!');
        } else {
          ConsoleViews.displayError('Échec de connexion: ${loginResult.data?['error']?['message'] ?? 'Erreur inconnue'}');
        }
      } else {
        ConsoleViews.displayError('Échec d\'envoi OTP: ${otpResult.data?['error']?['message'] ?? 'Erreur inconnue'}');
      }
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> logout() async {
    try {
      final result = await _authService.deconnexion();
      _handleAuthResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> viewAccount() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    try {
      final result = await _authService.consulterCompte();
      _handleAccountResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> viewProfile() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    try {
      final result = await _authService.consulterProfil();
      _handleProfileResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> changePin() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('Nouveau PIN (4 chiffres)');
    String? newPin = stdin.readLineSync();
    if (newPin == null) return;

    try {
      final request = ChangePinRequest(nouveauCodePin: newPin);
      final result = await _authService.changerPin(request);
      _handleAuthResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> checkBalance() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('ID du portefeuille');
    String? walletId = stdin.readLineSync();
    if (walletId == null) return;

    try {
      final result = await _walletService.consulterSolde(walletId);
      _handleWalletResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> transactionHistory() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('ID du portefeuille');
    String? walletId = stdin.readLineSync();
    if (walletId == null) return;

    try {
      final result = await _walletService.historiqueTransactions(walletId);
      _handleTransactionHistoryResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> transactionDetails() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('ID du portefeuille');
    String? walletId = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('ID de transaction');
    String? transactionId = stdin.readLineSync();
    if (walletId == null || transactionId == null) return;

    try {
      final result = await _walletService.detailsTransaction(walletId, transactionId);
      _handleTransactionDetailsResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> transferMoney() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('Téléphone du destinataire');
    String? recipient = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Montant');
    String? amount = stdin.readLineSync();
    if (recipient == null || amount == null) return;

    try {
      final request = TransferRequest(
        numeroTelephoneDestinataire: recipient,
        montant: double.parse(amount),
      );
      final result = await _transferService.effectuerTransfert(request);
      _handleTransferResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> cancelTransfer() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('ID du transfert');
    String? transferId = stdin.readLineSync();
    if (transferId == null) return;

    try {
      final result = await _transferService.annulerTransfert(transferId);
      _handleCancelTransferResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  Future<void> makePayment() async {
    if (!await CacheManager.hasAccessToken()) {
      ConsoleViews.displayError('Veuillez vous connecter d\'abord.');
      return;
    }

    ConsoleViews.displayInputPrompt('ID du marchand');
    String? merchantId = stdin.readLineSync();
    ConsoleViews.displayInputPrompt('Montant');
    String? amount = stdin.readLineSync();
    if (merchantId == null || amount == null) return;

    try {
      final request = PaymentRequest(
        idMarchand: merchantId,
        montant: double.parse(amount),
      );
      final result = await _paymentService.effectuerPaiement(request);
      _handlePaymentResult(result);
    } catch (e) {
      ConsoleViews.displayError('Erreur: $e');
    }
  }

  void _handleAuthResult(AuthResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data);
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleTransferResult(TransferResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.transfer.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handlePaymentResult(PaymentResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.payment.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleCancelTransferResult(CancelTransferResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data);
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleWalletResult(BalanceResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleTransactionHistoryResult(TransactionHistoryResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleTransactionDetailsResult(TransactionDetailsResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.transaction.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleAccountResult(AccountResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }

  void _handleProfileResult(ProfileResponse result) {
    if (result.success) {
      ConsoleViews.displaySuccess(result.message, result.data?.utilisateur.toJson());
    } else {
      ConsoleViews.displayError('Erreur inconnue');
    }
  }
}