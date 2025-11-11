<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    use HasUuids;

    protected $table = 'contacts';

    protected $fillable = [
        'id_utilisateur',
        'nom',
        'numero_telephone',
        'nombre_transactions',
        'derniere_transaction',
    ];

    /**
     * UUID primary key settings
     */
    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'derniere_transaction' => 'datetime',
    ];

    // Relationships
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    // Scopes
    public function scopeFrequents($query)
    {
        return $query->orderBy('nombre_transactions', 'desc');
    }

    public function scopeRecents($query)
    {
        return $query->orderBy('derniere_transaction', 'desc');
    }

    public function scopeParUtilisateur($query, $utilisateurId)
    {
        return $query->where('id_utilisateur', $utilisateurId);
    }

    // Methods
    public function ajouter(): bool
    {
        return $this->save();
    }

    public function lister()
    {
        return self::parUtilisateur($this->id_utilisateur)
            ->frequents()
            ->get();
    }

    public function incrementerTransactions(): bool
    {
        $this->increment('nombre_transactions');
        return $this->update(['derniere_transaction' => now()]);
    }

    public function mettreAJourDerniereTransaction(): bool
    {
        return $this->update(['derniere_transaction' => now()]);
    }

    public function obtenirInformations(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'numero_telephone' => $this->numero_telephone,
            'nombre_transactions' => $this->nombre_transactions,
            'derniere_transaction' => $this->derniere_transaction,
        ];
    }
}
