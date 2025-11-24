/// DTO pour les opérations de transfert
library;

import '../../models/entities/transfer.dart';

/// Requête d'effectuation de transfert
class TransferRequest {
  final String numeroTelephoneDestinataire;
  final double montant;

  TransferRequest({
    required this.numeroTelephoneDestinataire,
    required this.montant,
  });

  Map<String, dynamic> toJson() {
    return {
      'numeroTelephoneDestinataire': numeroTelephoneDestinataire,
      'montant': montant,
    };
  }
}

/// Réponse d'effectuation de transfert
class TransferResponse {
  final bool success;
  final String message;
  final TransferData? data;

  TransferResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory TransferResponse.fromJson(Map<String, dynamic> json) {
    return TransferResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? TransferData.fromJson(json['data']) : null,
    );
  }
}

/// Données de transfert
class TransferData {
  final Transfer transfer;

  TransferData({required this.transfer});

  factory TransferData.fromJson(Map<String, dynamic> json) {
    return TransferData(
      transfer: Transfer.fromJson(json),
    );
  }

  Map<String, dynamic> toJson() {
    return transfer.toJson();
  }
}

/// Requête d'annulation de transfert
class CancelTransferRequest {
  final String transferId;

  CancelTransferRequest({required this.transferId});

  Map<String, dynamic> toJson() {
    return {};
  }
}

/// Réponse d'annulation de transfert
class CancelTransferResponse {
  final bool success;
  final String message;
  final Map<String, dynamic>? data;

  CancelTransferResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory CancelTransferResponse.fromJson(Map<String, dynamic> json) {
    return CancelTransferResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] as Map<String, dynamic>?,
    );
  }
}