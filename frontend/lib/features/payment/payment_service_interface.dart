import '../../models/models.dart';

abstract class PaymentServiceInterface {
  Future<PaymentResponse> effectuerPaiement(PaymentRequest request);
}