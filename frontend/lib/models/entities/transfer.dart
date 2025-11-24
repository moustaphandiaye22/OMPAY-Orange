/// Entité Transfert - correspond au modèle Laravel Transfert
library;
import '../entities/transaction.dart';

class Transfer {
  final String id;
  final String? idTransaction;
  final String idExpediteur;
  final String? idDestinataire;
  final String numeroTelephoneDestinataire;
  final String? nomDestinataire;
  final String? note;
  final String statut;
  final DateTime? dateExpiration;
  final Transaction? transaction;

  Transfer({
    required this.id,
    this.idTransaction,
    required this.idExpediteur,
    this.idDestinataire,
    required this.numeroTelephoneDestinataire,
    this.nomDestinataire,
    this.note,
    required this.statut,
    this.dateExpiration,
    this.transaction,
  });

  /// Crée une instance Transfer depuis un Map (réponse API)
  factory Transfer.fromJson(Map<String, dynamic> json) {
    return Transfer(
      id: json['id'] as String,
      idTransaction: json['idTransaction'] ?? json['id_transaction'] as String?,
      idExpediteur: json['idExpediteur'] ?? json['id_expediteur'] as String,
      idDestinataire: json['idDestinataire'] ?? json['id_destinataire'] as String?,
      numeroTelephoneDestinataire: json['numeroTelephoneDestinataire'] ?? json['numero_telephone_destinataire'] as String,
      nomDestinataire: json['nomDestinataire'] ?? json['nom_destinataire'] as String?,
      note: json['note'] as String?,
      statut: json['statut'] as String,
      dateExpiration: json['dateExpiration'] != null
          ? DateTime.parse(json['dateExpiration'])
          : json['date_expiration'] != null
              ? DateTime.parse(json['date_expiration'])
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
      'idExpediteur': idExpediteur,
      'idDestinataire': idDestinataire,
      'numeroTelephoneDestinataire': numeroTelephoneDestinataire,
      'nomDestinataire': nomDestinataire,
      'note': note,
      'statut': statut,
      'dateExpiration': dateExpiration?.toIso8601String(),
      'transaction': transaction?.toJson(),
    };
  }

  /// Calcule les frais de transfert (1% avec minimum 50 FCFA)
  double calculerFrais() {
    if (transaction == null) return 0.0;
    final frais = transaction!.montant * 0.01;
    return frais > 50 ? frais : 50;
  }

  /// Vérifie si le transfert peut être annulé
  bool get peutEtreAnnule => !estExpire && transaction?.peutEtreAnnulee == true;

  /// Vérifie si le transfert est expiré
  bool get estExpire => dateExpiration?.isBefore(DateTime.now()) ?? false;

  /// Montant total avec frais
  double get montantTotal => (transaction?.montant ?? 0) + calculerFrais();

  @override
  String toString() {
    return 'Transfer{id: $id, destinataire: $numeroTelephoneDestinataire, montant: ${transaction?.montantFormate ?? 'N/A'}, statut: $statut}';
  }
}