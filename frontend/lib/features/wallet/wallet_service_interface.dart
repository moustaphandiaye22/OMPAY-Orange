import '../../models/models.dart';

abstract class WalletServiceInterface {
  Future<BalanceResponse> consulterSolde(String walletId);
  Future<TransactionHistoryResponse> historiqueTransactions(String walletId, {int page = 1, int limit = 10});
  Future<TransactionDetailsResponse> detailsTransaction(String walletId, String transactionId);
}