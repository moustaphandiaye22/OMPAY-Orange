<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qr_codes';

    protected $fillable = [
        'id',
        'id_marchand',
        'id_utilisateur',
        'donnees',
        'montant',
        'date_generation',
        'date_expiration',
        'utilise',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_generation' => 'datetime',
        'date_expiration' => 'datetime',
        'utilise' => 'boolean',
    ];

    // Relationships
    public function marchand(): BelongsTo
    {
        return $this->belongsTo(Marchand::class, 'id_marchand');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class, 'id_qr_code');
    }

    // Scopes
    public function scopeNonUtilises($query)
    {
        return $query->where('utilise', false);
    }

    public function scopeValides($query)
    {
        return $query->where('utilise', false)
            ->where('date_expiration', '>', now());
    }

    public function scopeExpires($query)
    {
        return $query->where('date_expiration', '<=', now());
    }

    // Methods
    public function generer(): string
    {
        // Générer le contenu du QR code selon le type
        if ($this->id_marchand) {
            // QR code marchand (avec montant et expiration)
            return json_encode([
                'type' => 'marchand',
                'id' => $this->id,
                'marchand' => $this->marchand->nom,
                'montant' => $this->montant,
                'date_expiration' => $this->date_expiration->timestamp,
            ]);
        } elseif ($this->id_utilisateur) {
            // QR code utilisateur (pour recevoir des paiements) - retourne seulement le numéro de téléphone
            return $this->utilisateur->numero_telephone;
        }

        return '';
    }

    public static function decoder(string $donnees): ?array
    {
        // Essayer de décoder comme JSON d'abord (pour les QR marchands)
        $decoded = json_decode($donnees, true);

        if ($decoded && isset($decoded['type'])) {
            return $decoded;
        }

        // Si ce n'est pas du JSON, vérifier si c'est un numéro de téléphone (QR utilisateur)
        if (preg_match('/^\+?221[0-9]{9}$/', $donnees)) {
            return [
                'type' => 'utilisateur',
                'numero_telephone' => $donnees,
            ];
        }

        return null;
    }

    public function valider(): bool
    {
        // Les QR codes utilisateur n'expirent pas, seulement vérification d'utilisation
        if ($this->id_utilisateur) {
            return !$this->utilise;
        }

        // Les QR codes marchand expirent et peuvent être utilisés une fois
        return !$this->utilise && $this->date_expiration > now();
    }

    public function verifierExpiration(): bool
    {
        return $this->date_expiration <= now();
    }

    public function marquerCommeUtilise(): bool
    {
        return $this->update(['utilise' => true]);
    }
}
