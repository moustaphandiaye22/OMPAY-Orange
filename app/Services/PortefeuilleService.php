<?php

namespace App\Services;

use App\Models\Portefeuille;
use App\Models\Transaction;
use App\Interfaces\PortefeuilleServiceInterface;
use App\Traits\ServiceResponseTrait;
use App\Traits\DataFormattingTrait;
use Carbon\Carbon;

class PortefeuilleService implements PortefeuilleServiceInterface
{
    use ServiceResponseTrait, DataFormattingTrait;
    /**
     * Consulter le solde du portefeuille
     *
     * @param mixed $utilisateur
     * @return array
     */
    public function consulterSolde($utilisateur)
    {
        $portefeuille = $utilisateur->portefeuille;

        if (!$portefeuille) {
            return $this->errorResponse('WALLET_001', 'Portefeuille introuvable', [], 404);
        }

        return $this->successResponse([
            'idPortefeuille' => $portefeuille->id,
            'solde' => $portefeuille->solde,
            'soldeDisponible' => $portefeuille->solde,
            'soldeEnAttente' => 0, // Calculer si nécessaire
            'devise' => $portefeuille->devise,
        ]);
    }

    /**
     * Historique des transactions
     *
     * @param mixed $utilisateur
     * @param array $filters
     * @param int $page
     * @param int $limite
     * @return array
     */
    public function historiqueTransactions($utilisateur, $filters, $page, $limite)
    {
        $portefeuille = $utilisateur->portefeuille;

        if (!$portefeuille) {
            return $this->errorResponse('WALLET_001', 'Portefeuille introuvable', [], 404);
        }

        $query = Transaction::where('id_portefeuille', $portefeuille->id);

        if (isset($filters['type']) && $filters['type'] !== 'tous') {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['dateDebut'])) {
            $query->whereDate('date_transaction', '>=', $filters['dateDebut']);
        }

        if (isset($filters['dateFin'])) {
            $query->whereDate('date_transaction', '<=', $filters['dateFin']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        $transactions = $query->orderBy('date_transaction', 'desc')
                              ->paginate($limite, ['*'], 'page', $page);

        $data = $transactions->map(function ($transaction) {
            $destinataire = null;
            $marchand = null;

            if ($transaction->type === 'transfert') {
                $transfert = $transaction->transfert;
                if ($transfert) {
                    $destinataire = [
                        'numeroTelephone' => $transfert->destinataire?->numero_telephone ?? $transfert->numero_destinataire ?? null,
                        'nom' => $transfert->nom_destinataire ?? $transfert->destinataire?->prenom . ' ' . $transfert->destinataire?->nom,
                    ];
                }
            } elseif ($transaction->type === 'paiement') {
                $paiement = $transaction->paiement;
                if ($paiement && $paiement->marchand) {
                    $marchand = [
                        'nom' => $paiement->marchand->nom,
                        'categorie' => $paiement->marchand->categorie ?? 'General',
                    ];
                }
            }

            return $this->formatTransactionData($transaction, [
                'destinataire' => $destinataire,
                'marchand' => $marchand,
            ]);
        });

        return $this->successResponse([
            'transactions' => $data,
            'pagination' => [
                'pageActuelle' => $transactions->currentPage(),
                'totalPages' => $transactions->lastPage(),
                'totalElements' => $transactions->total(),
                'elementsParPage' => $transactions->perPage(),
            ]
        ]);
    }

    /**
     * Détails d'une transaction
     *
     * @param mixed $utilisateur
     * @param string $idTransaction
     * @return array
     */
    public function detailsTransaction($utilisateur, $idTransaction)
    {
        $transaction = Transaction::where('id', $idTransaction)
                                  ->whereHas('portefeuille', function ($q) use ($utilisateur) {
                                      $q->where('id_utilisateur', $utilisateur->id);
                                  })
                                  ->first();

        if (!$transaction) {
            return $this->errorResponse('WALLET_001', 'Transaction introuvable', [], 404);
        }

        $expediteur = null;
        $destinataire = null;

        if ($transaction->type === 'transfert') {
            $expediteur = [
                'numeroTelephone' => $utilisateur->numero_telephone,
                'nom' => ($utilisateur->prenom ?? '') . ' ' . ($utilisateur->nom ?? ''),
            ];
            $transfert = $transaction->transfert;
            if ($transfert) {
                $destinataire = [
                    'numeroTelephone' => $transfert->destinataire?->numero_telephone ?? $transfert->numero_destinataire ?? null,
                    'nom' => $transfert->nom_destinataire ?? ($transfert->destinataire?->prenom . ' ' . $transfert->destinataire?->nom),
                ];
            }
        }

        return $this->successResponse($this->formatTransactionData($transaction, [
            'expediteur' => $expediteur,
            'destinataire' => $destinataire,
            'note' => $transaction->transfert ? $transaction->transfert->note ?? null : null,
        ]));
    }
}
