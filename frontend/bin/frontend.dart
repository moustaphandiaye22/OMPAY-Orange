import 'package:frontend/console_app.dart';
import 'package:frontend/utils/service_locator.dart';

void main() async {
  final services = ServiceLocator();
  // Attendre l'initialisation asynchrone du cache
  await Future.delayed(Duration(milliseconds: 100)); // Petit délai pour s'assurer que l'initialisation est terminée
  final app = ConsoleApp(services);
  await app.run();
}
