/// DTO pour les opérations de paiement
library;

import '../../models/entities/payment.dart';

/// Requête d'effectuation de paiement
class PaymentRequest {
  final String idMarchand;
  final double montant;

  PaymentRequest({
    required this.idMarchand,
    required this.montant,
  });

  Map<String, dynamic> toJson() {
    return {
      'idMarchand': idMarchand,
      'montant': montant,
    };
  }
}

/// Réponse d'effectuation de paiement
class PaymentResponse {
  final bool success;
  final String message;
  final PaymentData? data;

  PaymentResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory PaymentResponse.fromJson(Map<String, dynamic> json) {
    return PaymentResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? PaymentData.fromJson(json['data']) : null,
    );
  }
}

/// Données de paiement
class PaymentData {
  final Payment payment;

  PaymentData({required this.payment});

  factory PaymentData.fromJson(Map<String, dynamic> json) {
    return PaymentData(
      payment: Payment.fromJson(json),
    );
  }

  Map<String, dynamic> toJson() {
    return payment.toJson();
  }
}