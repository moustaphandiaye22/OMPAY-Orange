<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrangeMoney extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'orange_money';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'numero_telephone',
        'prenom',
        'nom',
        'email',
        'numero_cni',
        'statut_compte',
        'solde',
        'devise',
        'date_creation_compte',
        'derniere_connexion',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'date_creation_compte' => 'datetime',
        'derniere_connexion' => 'datetime',
    ];

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut_compte', 'actif');
    }

    public function scopeParTelephone($query, $telephone)
    {
        return $query->where('numero_telephone', $telephone);
    }

    // Methods
    public static function verifierExistenceCompte(string $numeroTelephone): ?self
    {
        return self::where('numero_telephone', $numeroTelephone)
                  ->where('statut_compte', 'actif')
                  ->first();
    }

    public function mettreAJourConnexion(): bool
    {
        return $this->update(['derniere_connexion' => now()]);
    }

    public function estActif(): bool
    {
        return $this->statut_compte === 'actif';
    }
}
