/// Entité Paiement - correspond au modèle Laravel Paiement
library;
import '../entities/transaction.dart';

class Payment {
  final String id;
  final String? idTransaction;
  final String idUtilisateur;
  final String idMarchand;
  final String? referencePaiement;
  final String statut;
  final DateTime? datePaiement;
  final Transaction? transaction;

  Payment({
    required this.id,
    this.idTransaction,
    required this.idUtilisateur,
    required this.idMarchand,
    this.referencePaiement,
    required this.statut,
    this.datePaiement,
    this.transaction,
  });

  /// Crée une instance Payment depuis un Map (réponse API)
  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'] as String,
      idTransaction: json['idTransaction'] ?? json['id_transaction'] as String?,
      idUtilisateur: json['idUtilisateur'] ?? json['id_utilisateur'] as String,
      idMarchand: json['idMarchand'] ?? json['id_marchand'] as String,
      referencePaiement: json['referencePaiement'] ?? json['reference_paiement'] as String?,
      statut: json['statut'] as String,
      datePaiement: json['datePaiement'] != null
          ? DateTime.parse(json['datePaiement'])
          : json['date_paiement'] != null
              ? DateTime.parse(json['date_paiement'])
              : null,
      transaction: json['transaction'] != null
          ? Transaction.fromJson(json['transaction'])
          : null,
    );
  }

  /// Convertit l'instance en Map pour les requêtes API
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'idTransaction': idTransaction,
      'idUtilisateur': idUtilisateur,
      'idMarchand': idMarchand,
      'referencePaiement': referencePaiement,
      'statut': statut,
      'datePaiement': datePaiement?.toIso8601String(),
      'transaction': transaction?.toJson(),
    };
  }

  /// Vérifie si le paiement est réussi
  bool get estReussi => statut == 'reussi';

  /// Vérifie si le paiement est en cours
  bool get estEnCours => statut == 'en_cours';

  /// Montant du paiement
  double get montant => transaction?.montant ?? 0.0;

  /// Formate le montant pour l'affichage
  String get montantFormate => transaction?.montantFormate ?? '0.00 FCFA';

  @override
  String toString() {
    return 'Payment{id: $id, marchand: $idMarchand, montant: $montantFormate, statut: $statut}';
  }
}