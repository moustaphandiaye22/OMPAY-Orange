import 'dart:convert';
import 'dart:io';

/// Gestionnaire de cache sécurisé pour le stockage des tokens d'authentification
class CacheManager {
  static const String _cacheDir = 'cache';
  static const String _tokenFile = 'auth_token.json';
  static const String _refreshTokenFile = 'refresh_token.json';

  /// Initialise le répertoire de cache
  static Future<void> initialize() async {
    final cacheDirectory = Directory(_cacheDir);
    if (!await cacheDirectory.exists()) {
      await cacheDirectory.create(recursive: true);
    }
  }

  /// Stocke le token d'accès de manière sécurisée
  static Future<void> setAccessToken(String token) async {
    await initialize();
    final file = File('$_cacheDir/$_tokenFile');
    final data = {
      'token': token,
      'timestamp': DateTime.now().toIso8601String(),
    };
    await file.writeAsString(jsonEncode(data), mode: FileMode.write);
  }

  /// Récupère le token d'accès
  static Future<String?> getAccessToken() async {
    try {
      final file = File('$_cacheDir/$_tokenFile');
      if (!await file.exists()) return null;

      final content = await file.readAsString();
      final data = jsonDecode(content) as Map<String, dynamic>;

      // Vérifier si le token n'est pas expiré (optionnel, peut être géré côté serveur)
      final timestamp = DateTime.parse(data['timestamp']);
      final now = DateTime.now();
      final difference = now.difference(timestamp);

      // Considérer le token valide pour 1 heure (ajustable)
      if (difference.inHours < 1) {
        return data['token'] as String;
      } else {
        // Token expiré, le supprimer
        await clearAccessToken();
        return null;
      }
    } catch (e) {
      // En cas d'erreur, supprimer le fichier corrompu
      await clearAccessToken();
      return null;
    }
  }

  /// Stocke le token de rafraîchissement
  static Future<void> setRefreshToken(String token) async {
    await initialize();
    final file = File('$_cacheDir/$_refreshTokenFile');
    final data = {
      'token': token,
      'timestamp': DateTime.now().toIso8601String(),
    };
    await file.writeAsString(jsonEncode(data), mode: FileMode.write);
  }

  /// Récupère le token de rafraîchissement
  static Future<String?> getRefreshToken() async {
    try {
      final file = File('$_cacheDir/$_refreshTokenFile');
      if (!await file.exists()) return null;

      final content = await file.readAsString();
      final data = jsonDecode(content) as Map<String, dynamic>;
      return data['token'] as String;
    } catch (e) {
      await clearRefreshToken();
      return null;
    }
  }

  /// Supprime le token d'accès
  static Future<void> clearAccessToken() async {
    final file = File('$_cacheDir/$_tokenFile');
    if (await file.exists()) {
      await file.delete();
    }
  }

  /// Supprime le token de rafraîchissement
  static Future<void> clearRefreshToken() async {
    final file = File('$_cacheDir/$_refreshTokenFile');
    if (await file.exists()) {
      await file.delete();
    }
  }

  /// Supprime tous les tokens (déconnexion)
  static Future<void> clearAllTokens() async {
    await clearAccessToken();
    await clearRefreshToken();
  }

  /// Vérifie si un token d'accès est disponible
  static Future<bool> hasAccessToken() async {
    final token = await getAccessToken();
    return token != null;
  }

  /// Vérifie si un token de rafraîchissement est disponible
  static Future<bool> hasRefreshToken() async {
    final token = await getRefreshToken();
    return token != null;
  }
}