import '../../models/models.dart';

abstract class AuthServiceInterface {
  Future<AuthResponse> creerCompte(RegisterRequest request);
  Future<AuthResponse> finaliserInscription(FinalizeRegistrationRequest request);
  Future<AuthResponse> verificationOtp(VerifyOtpRequest request);
  Future<AuthResponse> connexion(LoginRequest request);
  Future<AuthResponse> rafraichir(String refreshToken);
  Future<AuthResponse> deconnexion();
  Future<AccountResponse> consulterCompte();
  Future<ProfileResponse> consulterProfil();
  Future<AuthResponse> changerPin(ChangePinRequest request);
}