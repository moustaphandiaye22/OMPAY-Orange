import '../../models/models.dart';

abstract class TransferServiceInterface {
  Future<TransferResponse> effectuerTransfert(TransferRequest request);
  Future<CancelTransferResponse> annulerTransfert(String transferId);
}