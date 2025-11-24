import 'dart:io';
import 'utils/service_locator.dart';
import 'services/console_service.dart';
import 'views/console_views.dart';

class ConsoleApp {
  final ServiceLocator services;
  late final ConsoleService consoleService;

  ConsoleApp(this.services) {
    consoleService = ConsoleService(
      services.authService,
      services.walletService,
      services.transferService,
      services.paymentService,
    );
  }

  Future<void> run() async {
    ConsoleViews.displayWelcome();
    while (true) {
      ConsoleViews.displayMainMenu();
      String? choice = _getUserInput('Votre choix');
      if (choice == null) continue;

      switch (choice) {
        case '1':
          await consoleService.registerAccount();
          break;
        case '2':
          await consoleService.finalizeRegistration();
          break;
        case '3':
          await consoleService.login();
          break;
        case '4':
          await consoleService.logout();
          break;
        case '5':
          await consoleService.viewAccount();
          break;
        case '6':
          await consoleService.viewProfile();
          break;
        case '7':
          await consoleService.changePin();
          break;
        case '8':
          await consoleService.checkBalance();
          break;
        case '9':
          await consoleService.transactionHistory();
          break;
        case '10':
          await consoleService.transactionDetails();
          break;
        case '11':
          await consoleService.transferMoney();
          break;
        case '12':
          await consoleService.cancelTransfer();
          break;
        case '13':
          await consoleService.makePayment();
          break;
        case '0':
          ConsoleViews.displayGoodbye();
          return;
        default:
          ConsoleViews.displayError('Choix invalide. Veuillez réessayer.');
      }
      ConsoleViews.pause();
    }
  }

  String? _getUserInput(String prompt) {
    ConsoleViews.displayInputPrompt(prompt);
    return stdin.readLineSync();
  }
}