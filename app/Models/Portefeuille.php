<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Portefeuille extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'portefeuilles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id_utilisateur',
        'solde',
        'devise',
    ];

    protected $guarded = [];

    protected $casts = [
        'solde' => 'decimal:2',
    ];

    // Relationships
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'id_portefeuille');
    }

    // Scopes
    public function scopeAvecSoldePositif($query)
    {
        return $query->where('solde', '>', 0);
    }

    public function scopeParDevise($query, $devise)
    {
        return $query->where('devise', $devise);
    }

    // Methods
    public function consulterSolde(): float
    {
        return $this->solde;
    }

    public function verifierFondsSuffisants(float $montant): bool
    {
        return $this->solde >= $montant;
    }

    public function debiter(float $montant): bool
    {
        if (!$this->verifierFondsSuffisants($montant)) {
            return false;
        }

        DB::transaction(function () use ($montant) {
            $this->decrement('solde', $montant);
        });

        return true;
    }

    public function crediter(float $montant): bool
    {
        DB::transaction(function () use ($montant) {
            $this->increment('solde', $montant);
        });

        return true;
    }

    public function calculerSoldeApresTransaction(float $montant, string $type = 'debit'): float
    {
        return $type === 'debit' ? $this->solde - $montant : $this->solde + $montant;
    }

    public function transfererVers(Portefeuille $destinataire, float $montant): bool
    {
        if (!$this->verifierFondsSuffisants($montant)) {
            return false;
        }

        DB::transaction(function () use ($destinataire, $montant) {
            $this->debiter($montant);
            $destinataire->crediter($montant);
        });

        return true;
    }
}
