/// DTO pour les opérations d'authentification
library;
import '../../models/entities/user.dart';
import '../../models/entities/wallet.dart';
import '../../models/entities/transaction.dart';

/// Requête d'inscription initiale
class RegisterRequest {
  final String prenom;
  final String nom;
  final String numeroTelephone;
  final String email;
  final String numeroCni;

  RegisterRequest({
    required this.prenom,
    required this.nom,
    required this.numeroTelephone,
    required this.email,
    required this.numeroCni,
  });

  Map<String, dynamic> toJson() {
    return {
      'prenom': prenom,
      'nom': nom,
      'numeroTelephone': numeroTelephone,
      'email': email,
      'numeroCni': numeroCni,
    };
  }
}

/// Requête de finalisation d'inscription
class FinalizeRegistrationRequest {
  final String numeroTelephone;
  final String codeOtp;
  final String email;
  final String codePin;
  final String numeroCni;

  FinalizeRegistrationRequest({
    required this.numeroTelephone,
    required this.codeOtp,
    required this.email,
    required this.codePin,
    required this.numeroCni,
  });

  Map<String, dynamic> toJson() {
    return {
      'numeroTelephone': numeroTelephone,
      'codeOTP': codeOtp,
      'email': email,
      'codePin': codePin,
      'numeroCNI': numeroCni,
    };
  }
}

/// Requête de connexion
class LoginRequest {
  final String numeroTelephone;
  final String? codeOtp;

  LoginRequest({
    required this.numeroTelephone,
    this.codeOtp,
  });

  Map<String, dynamic> toJson() {
    final data = {'numeroTelephone': numeroTelephone};
    if (codeOtp != null) {
      data['codeOTP'] = codeOtp!;
    }
    return data;
  }
}

/// Requête de vérification OTP
class VerifyOtpRequest {
  final String numeroTelephone;
  final String codeOtp;

  VerifyOtpRequest({
    required this.numeroTelephone,
    required this.codeOtp,
  });

  Map<String, dynamic> toJson() {
    return {
      'numeroTelephone': numeroTelephone,
      'codeOTP': codeOtp,
    };
  }
}

/// Requête de changement de PIN
class ChangePinRequest {
  final String nouveauCodePin;

  ChangePinRequest({
    required this.nouveauCodePin,
  });

  Map<String, dynamic> toJson() {
    return {
      'nouveauCodePin': nouveauCodePin,
    };
  }
}

/// Réponse d'authentification
class AuthResponse {
  final bool success;
  final String message;
  final Map<String, dynamic>? data;

  AuthResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] as Map<String, dynamic>?,
    );
  }

  /// Token d'accès depuis la réponse
  String? get accessToken => data?['jetonAcces'];

  /// Token de rafraîchissement depuis la réponse
  String? get refreshToken => data?['jetonRafraichissement'];

  /// Utilisateur depuis la réponse
  User? get user => data?['utilisateur'] != null
      ? User.fromJson(data!['utilisateur'])
      : null;
}

/// Réponse de consultation de compte
class AccountResponse {
  final bool success;
  final String message;
  final AccountData? data;

  AccountResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory AccountResponse.fromJson(Map<String, dynamic> json) {
    return AccountResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? AccountData.fromJson(json['data']) : null,
    );
  }
}

/// Données du compte
class AccountData {
  final User utilisateur;
  final Wallet compte;
  final List<Transaction> transactions;

  AccountData({
    required this.utilisateur,
    required this.compte,
    required this.transactions,
  });

  factory AccountData.fromJson(Map<String, dynamic> json) {
    final transactionsList = (json['transactions'] as List<dynamic>?)
        ?.map((item) => Transaction.fromJson(item as Map<String, dynamic>))
        .toList() ?? [];

    return AccountData(
      utilisateur: User.fromJson(json['utilisateur']),
      compte: Wallet.fromJson(json['compte']),
      transactions: transactionsList,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'utilisateur': utilisateur.toJson(),
      'compte': compte.toJson(),
      'transactions': transactions.map((t) => t.toJson()).toList(),
    };
  }
}

/// Réponse de consultation de profil
class ProfileResponse {
  final bool success;
  final String message;
  final ProfileData? data;

  ProfileResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory ProfileResponse.fromJson(Map<String, dynamic> json) {
    return ProfileResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? ProfileData.fromJson(json['data']) : null,
    );
  }
}

/// Données du profil
class ProfileData {
  final User utilisateur;

  ProfileData({required this.utilisateur});

  factory ProfileData.fromJson(Map<String, dynamic> json) {
    return ProfileData(
      utilisateur: User.fromJson(json['utilisateur']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'utilisateur': utilisateur.toJson(),
    };
  }
}