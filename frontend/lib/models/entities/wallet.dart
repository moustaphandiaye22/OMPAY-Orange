/// Entité Portefeuille - correspond au modèle Laravel Portefeuille
class Wallet {
  final String id;
  final String idUtilisateur;
  final double solde;
  final String devise;

  Wallet({
    required this.id,
    required this.idUtilisateur,
    required this.solde,
    required this.devise,
  });

  /// Crée une instance Wallet depuis un Map (réponse API)
  factory Wallet.fromJson(Map<String, dynamic> json) {
    return Wallet(
      id: json['id'] as String,
      idUtilisateur: json['idUtilisateur'] ?? json['id_utilisateur'] as String,
      solde: (json['solde'] as num).toDouble(),
      devise: json['devise'] ?? 'FCFA',
    );
  }

  /// Convertit l'instance en Map pour les requêtes API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'idUtilisateur': idUtilisateur,
      'solde': solde,
      'devise': devise,
    };
  }

  /// Vérifie si le portefeuille a des fonds suffisants
  bool verifierFondsSuffisants(double montant) {
    return solde >= montant;
  }

  /// Calcule le solde après une transaction
  double calculerSoldeApresTransaction(double montant, String type) {
    return type == 'debit' ? solde - montant : solde + montant;
  }

  /// Formate le solde pour l'affichage
  String get soldeFormate => '${solde.toStringAsFixed(2)} $devise';

  @override
  String toString() {
    return 'Wallet{id: $id, solde: $soldeFormate}';
  }
}