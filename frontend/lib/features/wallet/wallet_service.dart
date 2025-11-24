import 'wallet_service_interface.dart';
import '../../interfaces/api_service.dart';
import '../../models/models.dart';

class WalletService implements WalletServiceInterface {
  final ApiService _apiService;

  WalletService(this._apiService);

  @override
  Future<BalanceResponse> consulterSolde(String walletId) async {
    final apiResponse = await _apiService.post('/portefeuille/$walletId/solde', {});
    return BalanceResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<TransactionHistoryResponse> historiqueTransactions(String walletId, {int page = 1, int limit = 10}) async {
    final apiResponse = await _apiService.post('/portefeuille/$walletId/transactions', {});
    return TransactionHistoryResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<TransactionDetailsResponse> detailsTransaction(String walletId, String transactionId) async {
    final apiResponse = await _apiService.post('/portefeuille/$walletId/transactions/$transactionId', {});
    return TransactionDetailsResponse.fromJson(apiResponse.data!);
  }
}