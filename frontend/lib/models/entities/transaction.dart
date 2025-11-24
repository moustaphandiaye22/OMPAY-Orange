/// Entité Transaction - correspond au modèle Laravel Transaction
class Transaction {
  final String id;
  final String idPortefeuille;
  final String type;
  final double montant;
  final String devise;
  final String statut;
  final double? frais;
  final String? reference;
  final DateTime? dateTransaction;

  Transaction({
    required this.id,
    required this.idPortefeuille,
    required this.type,
    required this.montant,
    required this.devise,
    required this.statut,
    this.frais,
    this.reference,
    this.dateTransaction,
  });

  /// Crée une instance Transaction depuis un Map (réponse API)
  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'] ?? json['idTransaction'] as String,
      idPortefeuille: json['idPortefeuille'] ?? json['id_portefeuille'] as String,
      type: json['type'] as String,
      montant: (json['montant'] as num).toDouble(),
      devise: json['devise'] ?? 'FCFA',
      statut: json['statut'] as String,
      frais: json['frais'] != null ? (json['frais'] as num).toDouble() : null,
      reference: json['reference'] as String?,
      dateTransaction: json['dateTransaction'] != null
          ? DateTime.parse(json['dateTransaction'])
          : json['date_transaction'] != null
              ? DateTime.parse(json['date_transaction'])
              : null,
    );
  }

  /// Convertit l'instance en Map pour les requêtes API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'idPortefeuille': idPortefeuille,
      'type': type,
      'montant': montant,
      'devise': devise,
      'statut': statut,
      'frais': frais,
      'reference': reference,
      'dateTransaction': dateTransaction?.toIso8601String(),
    };
  }

  /// Vérifie si la transaction est réussie
  bool get estReussie => statut == 'reussie';

  /// Vérifie si la transaction est en cours
  bool get estEnCours => statut == 'en_cours';

  /// Vérifie si la transaction peut être annulée
  bool get peutEtreAnnulee => !['reussie', 'annulee'].contains(statut);

  /// Formate le montant pour l'affichage
  String get montantFormate => '${montant.toStringAsFixed(2)} $devise';

  /// Formate les frais pour l'affichage
  String get fraisFormate => frais != null ? '${frais!.toStringAsFixed(2)} $devise' : '0.00 $devise';

  /// Génère un reçu formaté
  Map<String, dynamic> genererRecu() {
    return {
      'reference': reference,
      'montant': montant,
      'frais': frais ?? 0.0,
      'date': dateTransaction,
      'statut': statut,
    };
  }

  @override
  String toString() {
    return 'Transaction{id: $id, type: $type, montant: $montantFormate, statut: $statut}';
  }
}