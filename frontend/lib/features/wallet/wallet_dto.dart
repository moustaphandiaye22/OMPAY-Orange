/// DTO pour les opérations de portefeuille
library;

import '../../models/entities/transaction.dart';

/// Requête de consultation de solde
class BalanceRequest {
  final String walletId;

  BalanceRequest({required this.walletId});

  Map<String, dynamic> toJson() {
    return {};
  }
}

/// Réponse de consultation de solde
class BalanceResponse {
  final bool success;
  final String message;
  final WalletData? data;

  BalanceResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory BalanceResponse.fromJson(Map<String, dynamic> json) {
    return BalanceResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? WalletData.fromJson(json['data']) : null,
    );
  }
}

/// Données du portefeuille
class WalletData {
  final String idPortefeuille;
  final double solde;

  WalletData({
    required this.idPortefeuille,
    required this.solde,
  });

  factory WalletData.fromJson(Map<String, dynamic> json) {
    return WalletData(
      idPortefeuille: json['idPortefeuille'] as String,
      solde: (json['solde'] as num).toDouble(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'idPortefeuille': idPortefeuille,
      'solde': solde,
    };
  }
}

/// Requête d'historique des transactions
class TransactionHistoryRequest {
  final String walletId;
  final int page;
  final int limit;

  TransactionHistoryRequest({
    required this.walletId,
    this.page = 1,
    this.limit = 10,
  });

  Map<String, dynamic> toJson() {
    return {};
  }
}

/// Réponse d'historique des transactions
class TransactionHistoryResponse {
  final bool success;
  final String message;
  final TransactionHistoryData? data;

  TransactionHistoryResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory TransactionHistoryResponse.fromJson(Map<String, dynamic> json) {
    return TransactionHistoryResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? TransactionHistoryData.fromJson(json['data']) : null,
    );
  }
}

/// Données d'historique des transactions
class TransactionHistoryData {
  final List<Transaction> transactions;
  final int currentPage;
  final int totalPages;
  final int totalTransactions;

  TransactionHistoryData({
    required this.transactions,
    required this.currentPage,
    required this.totalPages,
    required this.totalTransactions,
  });

  factory TransactionHistoryData.fromJson(Map<String, dynamic> json) {
    final transactionsList = (json['transactions'] as List<dynamic>?)
        ?.map((item) => Transaction.fromJson(item as Map<String, dynamic>))
        .toList() ?? [];

    return TransactionHistoryData(
      transactions: transactionsList,
      currentPage: json['currentPage'] ?? json['current_page'] ?? 1,
      totalPages: json['totalPages'] ?? json['total_pages'] ?? 1,
      totalTransactions: json['totalTransactions'] ?? json['total_transactions'] ?? transactionsList.length,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'transactions': transactions.map((t) => t.toJson()).toList(),
      'currentPage': currentPage,
      'totalPages': totalPages,
      'totalTransactions': totalTransactions,
    };
  }
}

/// Requête de détails de transaction
class TransactionDetailsRequest {
  final String walletId;
  final String transactionId;

  TransactionDetailsRequest({
    required this.walletId,
    required this.transactionId,
  });

  Map<String, dynamic> toJson() {
    return {};
  }
}

/// Réponse de détails de transaction
class TransactionDetailsResponse {
  final bool success;
  final String message;
  final TransactionData? data;

  TransactionDetailsResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory TransactionDetailsResponse.fromJson(Map<String, dynamic> json) {
    return TransactionDetailsResponse(
      success: json['success'] as bool,
      message: json['message'] as String,
      data: json['data'] != null ? TransactionData.fromJson(json['data']) : null,
    );
  }
}

/// Données de transaction détaillées
class TransactionData {
  final Transaction transaction;

  TransactionData({required this.transaction});

  factory TransactionData.fromJson(Map<String, dynamic> json) {
    return TransactionData(
      transaction: Transaction.fromJson(json),
    );
  }

  Map<String, dynamic> toJson() {
    return transaction.toJson();
  }
}