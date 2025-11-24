import 'auth_service_interface.dart';
import '../../interfaces/api_service.dart';
import '../../models/models.dart';
import '../../cache/cache_manager.dart';

class AuthService implements AuthServiceInterface {
  final ApiService _apiService;

  AuthService(this._apiService);

  @override
  Future<AuthResponse> creerCompte(RegisterRequest request ) async {
    final apiResponse = await _apiService.post('/auth/creercompte', request.toJson());
    return AuthResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<AuthResponse> finaliserInscription(FinalizeRegistrationRequest request) async {
    final apiResponse = await _apiService.post('/auth/finaliser-inscription', request.toJson());
    return AuthResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<AuthResponse> verificationOtp(VerifyOtpRequest request) async {
    final apiResponse = await _apiService.post('/auth/verification-otp', request.toJson());
    return AuthResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<AuthResponse> connexion(LoginRequest request) async {
    final apiResponse = await _apiService.post('/auth/connexion', request.toJson());
    final response = AuthResponse.fromJson(apiResponse.data!);
    if (response.success && response.data != null) {
      // Sauvegarder les tokens dans le cache automatiquement
      if (response.data!['jetonAcces'] != null) {
        await CacheManager.setAccessToken(response.data!['jetonAcces']);
      }
      if (response.data!['jetonRafraichissement'] != null) {
        await CacheManager.setRefreshToken(response.data!['jetonRafraichissement']);
      }
    }
    return response;
  }

  @override
  Future<AuthResponse> rafraichir(String refreshToken) async {
    final apiResponse = await _apiService.post('/auth/rafraichir', {
      'jetonRafraichissement': refreshToken,
    });
    final response = AuthResponse.fromJson(apiResponse.data!);
    if (response.success && response.data != null) {
      // Mettre à jour les tokens dans le cache automatiquement
      if (response.data!['jetonAcces'] != null) {
        await CacheManager.setAccessToken(response.data!['jetonAcces']);
      }
      if (response.data!['jetonRafraichissement'] != null) {
        await CacheManager.setRefreshToken(response.data!['jetonRafraichissement']);
      }
    }
    return response;
  }

  @override
  Future<AuthResponse> deconnexion() async {
    final apiResponse = await _apiService.post('/auth/deconnexion', {});
    final response = AuthResponse.fromJson(apiResponse.data!);
    if (response.success) {
      // Effacer tous les tokens du cache automatiquement
      await CacheManager.clearAllTokens();
    }
    return response;
  }

  @override
  Future<AccountResponse> consulterCompte() async {
    final apiResponse = await _apiService.get('/compte');
    return AccountResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<ProfileResponse> consulterProfil() async {
    final apiResponse = await _apiService.get('/utilisateurs/profil');
    return ProfileResponse.fromJson(apiResponse.data!);
  }

  @override
  Future<AuthResponse> changerPin(ChangePinRequest request) async {
    final apiResponse = await _apiService.post('/utilisateurs/changer-pin', request.toJson());
    return AuthResponse.fromJson(apiResponse.data!);
  }
}