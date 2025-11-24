import 'payment_service_interface.dart';
import '../../interfaces/api_service.dart';
import '../../models/models.dart';

class PaymentService implements PaymentServiceInterface {
  final ApiService _apiService;

  PaymentService(this._apiService);

  @override
  Future<PaymentResponse> effectuerPaiement(PaymentRequest request) async {
    final apiResponse = await _apiService.post('/paiement/effectuer-paiement', request.toJson());
    return PaymentResponse.fromJson(apiResponse.data!);
  }
}