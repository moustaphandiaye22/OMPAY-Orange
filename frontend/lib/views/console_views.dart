/// Vues pour l'interface console OMPAY
/// Séparation de la couche présentation (Views) de la logique métier
library;

import 'dart:io';

class ConsoleViews {
  static void displayWelcome() {
    print('''
╔══════════════════════════════════════════════════════════════╗
║                     OMPAY Orange Money                       ║
║                 Client Console - Version 1.0                 ║
║                                                              ║
║   Bienvenue dans l'application de gestion de compte Orange   ║
║   Money. Utilisez les options du menu pour naviguer.         ║
╚══════════════════════════════════════════════════════════════╝
''');
  }

  static void displayMainMenu() {
    print('''
┌───────────────────────────────────────────────────────────┐
│                        MENU PRINCIPAL                     │
├───────────────────────────────────────────────────────────┤
│  1.  Créer un nouveau compte                              │
│  2.  Finaliser l'inscription                              │
│  3.  Se connecter                                         │
│  4.  Se déconnecter                                       │
│  5.  Voir le tableau de bord                              │
│  6.  Voir le profil                                       │
│  7.  Changer le PIN                                       │
│  8.  Consulter le solde                                   │
│  9.  Historique des transactions                          │
│  10. Détails de transaction                               │
│  11. Transférer de l'argent                               │
│  12. Annuler un transfert                                 │
│  13. Effectuer un paiement                                │
│                                                           │
│  0.  Quitter                                              │
└───────────────────────────────────────────────────────────┘
''');
  }

  static void displayGoodbye() {
    print('''
╔══════════════════════════════════════════════════════════════╗
║                    👋 Au revoir!                             ║
║                                                              ║
║   Merci d'avoir utilisé OMPAY Orange Money Console Client.   ║
║   À bientôt!                                                 ║
╚══════════════════════════════════════════════════════════════╝
''');
  }

  static void displaySuccess(String message, [Map<String, dynamic>? data]) {
    print('✅ $message');
    if (data != null && data.isNotEmpty) {
      print('📄 Données:');
      _displayJson(data);
    }
  }

  static void displayError(String message, [Map<String, dynamic>? error]) {
    print('❌ $message');
    if (error != null && error.isNotEmpty) {
      print('⚠️  Détails:');
      _displayJson(error);
    }
  }

  static void displayInfo(String message) {
    print('ℹ️  $message');
  }

  static void displayWarning(String message) {
    print('⚠️  $message');
  }

  static void displayInputPrompt(String prompt) {
    stdout.write('$prompt: ');
  }

  static void displaySeparator() {
    print('\n${'=' * 60}\n');
  }

  static void displaySection(String title) {
    print('''
╔══════════════════════════════════════════════════════════════╗
║ $title${' ' * (60 - title.length - 2)} ║
╚══════════════════════════════════════════════════════════════╝
''');
  }

  static void _displayJson(Map<String, dynamic> data, [int indent = 0]) {
    final indentation = '  ' * indent;
    data.forEach((key, value) {
      if (value is Map<String, dynamic>) {
        print('$indentation$key:');
        _displayJson(value, indent + 1);
      } else if (value is List) {
        print('$indentation$key: [${value.length} éléments]');
        for (var i = 0; i < value.length && i < 3; i++) {
          if (value[i] is Map<String, dynamic>) {
            print('$indentation  [$i]:');
            _displayJson(value[i], indent + 2);
          } else {
            print('$indentation  [$i]: ${value[i]}');
          }
        }
        if (value.length > 3) {
          print('$indentation  ... et ${value.length - 3} autres');
        }
      } else {
        print('$indentation$key: $value');
      }
    });
  }

  static void pause() {
    print('\nAppuyez sur Entrée pour continuer...');
    stdin.readLineSync();
  }

  static void clearScreen() {
    // Pour les systèmes Unix/Linux/Mac
    print('\x1B[2J\x1B[0;0H');
  }
}