import 'transfer_service_interface.dart';
import '../../interfaces/api_service.dart';
import '../../models/models.dart';

class TransferService implements TransferServiceInterface {
  final ApiService _apiService;

  TransferService(this._apiService);

  @override
  Future<TransferResponse> effectuerTransfert(TransferRequest request) async {
    final apiResponse = await _apiService.post('/transfert/effectuer-transfert', request.toJson());
    return TransferResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<CancelTransferResponse> annulerTransfert(String transferId) async {
    final apiResponse = await _apiService.delete('/transfert/$transferId/annuler-transfert');
    return CancelTransferResponse.fromJson(apiResponse.data!);
  }
}