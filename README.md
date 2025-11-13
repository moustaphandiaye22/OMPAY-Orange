# 🚀 OMPAY - Solution de Paiement Mobile Orange Money

[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791.svg)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**OMPay** est une plateforme de paiement mobile moderne et sécurisée développée avec Laravel, permettant aux utilisateurs de gérer leurs transactions financières via Orange Money au Sénégal.

## 📋 Table des Matières

- [✨ Fonctionnalités](#-fonctionnalités)
- [🏗️ Architecture](#️-architecture)
- [🛠️ Technologies](#️-technologies)
- [📦 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [🚀 Utilisation](#-utilisation)
- [📚 API Documentation](#-api-documentation)
- [🧪 Tests](#-tests)
- [🔒 Sécurité](#-sécurité)
- [📊 Base de Données](#-base-de-données)
- [🚀 Déploiement](#-déploiement)
- [🤝 Contribution](#-contribution)
- [📄 Licence](#-licence)

## ✨ Fonctionnalités

### 🔐 Authentification & Utilisateurs
- ✅ Inscription avec vérification Orange Money
- ✅ Connexion sécurisée avec OTP SMS
- ✅ Gestion des profils utilisateurs
- ✅ Authentification JWT stateless
- ✅ Validation KYC (Know Your Customer)

### 💰 Gestion des Paiements
- ✅ **Paiements QR Code** - Scanner et payer instantanément
- ✅ **Paiements par Code** - Utiliser des codes de paiement marchands
- ✅ **Paiements Téléphoniques** - Payer directement avec numéro de téléphone
- ✅ **Historique complet** des transactions
- ✅ **Reçus PDF** générés automatiquement

### 🔄 Transferts d'Argent
- ✅ **Transferts P2P** entre utilisateurs
- ✅ **Vérification Orange Money** obligatoire
- ✅ **Notifications temps réel**
- ✅ **Historique des transferts**
- ✅ **Annulation de transferts** (sous conditions)

### 👛 Gestion du Portefeuille
- ✅ **Solde en temps réel**
- ✅ **Historique paginé** des transactions
- ✅ **Détails complets** de chaque opération
- ✅ **Devise FCFA** (Franc CFA)
- ✅ **Sécurité PIN** pour toutes les opérations

### 🏪 Gestion Marchands
- ✅ **Inscription marchands**
- ✅ **Génération QR codes** dynamiques
- ✅ **Codes de paiement** à usage unique
- ✅ **Catégorisation** des marchands
- ✅ **Statistiques de vente**

## 🏗️ Architecture

```
OMPay/
├── app/
│   ├── Http/Controllers/     # Contrôleurs API REST
│   ├── Models/              # Modèles Eloquent
│   ├── Services/            # Logique métier
│   ├── Traits/              # Traits réutilisables
│   └── Interfaces/          # Contrats des services
├── database/
│   ├── migrations/          # Schéma de base de données
│   └── seeders/            # Données de test
├── routes/
│   └── api.php             # Routes API RESTful
├── config/                 # Configuration Laravel
├── resources/             # Ressources frontend (optionnel)
└── storage/
    └── api-docs/          # Documentation Swagger
```

### 🏛️ Architecture Logicielle

- **Clean Architecture** : Séparation claire des couches
- **Repository Pattern** : Abstraction de la persistance
- **Service Layer** : Logique métier isolée
- **DTO Pattern** : Objets de transfert de données
- **Observer Pattern** : Événements et notifications

## 🛠️ Technologies

### Backend
- **Laravel 11** - Framework PHP moderne
- **PHP 8.4** - Dernière version LTS
- **PostgreSQL 15** - Base de données robuste

### Sécurité & Authentification
- **JWT (JSON Web Tokens)** - Authentification stateless
- **OTP SMS** - Double authentification
- **bcrypt** - Hashage des mots de passe
- **Rate Limiting** - Protection contre les attaques

### API & Documentation
- **RESTful API** - Architecture REST complète
- **Swagger/OpenAPI** - Documentation interactive
- **CORS** - Gestion cross-origin
- **JSON** - Format de données standard

### Outils de Développement
- **Composer** - Gestion des dépendances PHP
- **Artisan** - Interface en ligne de commande
- **Telescope** - Debugging et monitoring (dev)
- **Horizon** - Gestion des queues (optionnel)

## 📦 Installation

### Prérequis
- PHP 8.4 ou supérieur
- Composer
- PostgreSQL 15
- Node.js & npm (pour assets frontend)

### Installation étape par étape

1. **Cloner le repository**
```bash
git clone https://github.com/votre-username/ompay.git
cd ompay
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration de la base de données**
```bash
# Créer une base de données PostgreSQL
createdb ompay_db

# Configurer .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ompay_db
DB_USERNAME=votre_username
DB_PASSWORD=votre_password
```

5. **Migration et seed**
```bash
php artisan migrate
php artisan db:seed
```

6. **Génération de la documentation**
```bash
php artisan l5-swagger:generate
```

7. **Démarrage du serveur**
```bash
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`

## ⚙️ Configuration

### Variables d'environnement (.env)

```env
# Application
APP_NAME=OMPay
APP_ENV=local
APP_KEY=base64_generated_key
APP_DEBUG=true
APP_URL=http://localhost

# Base de données
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ompay_db
DB_USERNAME=ompay_user
DB_PASSWORD=secure_password

# JWT
JWT_SECRET=your_jwt_secret_key

# Services externes
SMS_SERVICE_URL=https://api.sms-provider.com
ORANGE_MONEY_API_URL=https://api.orange.sn
SMS_API_KEY=your_sms_api_key

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Configuration des services

#### Service SMS
```php
// config/services.php
'sms' => [
    'url' => env('SMS_SERVICE_URL'),
    'api_key' => env('SMS_API_KEY'),
    'sender' => 'OMPay',
],
```

#### Orange Money API
```php
// config/services.php
'orange_money' => [
    'url' => env('ORANGE_MONEY_API_URL'),
    'client_id' => env('OM_CLIENT_ID'),
    'client_secret' => env('OM_CLIENT_SECRET'),
],
```

## 🚀 Utilisation

### 🔐 Flux d'authentification

1. **Création de compte**
```bash
curl -X POST http://localhost:8000/api/auth/creercompte \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "prenom": "Moustapha",
    "nom": "Ndiaye",
    "numeroTelephone": "+221771411251",
    "email": "moustapha@example.com",
    "numeroCNI": "1234567890123"
  }'
```

2. **Vérification OTP**
```bash
curl -X POST http://localhost:8000/api/auth/verification-otp \
  -H "Content-Type: application/json" \
  -d '{
    "numeroTelephone": "+221771411251",
    "codeOTP": "123456"
  }'
```

3. **Connexion**
```bash
curl -X POST http://localhost:8000/api/auth/connexion \
  -H "Content-Type: application/json" \
  -d '{
    "numeroTelephone": "+221771411251"
  }'
```

### 💰 Opérations financières

#### Effectuer un paiement
```bash
curl -X POST http://localhost:8000/api/paiement/effectuer-paiement \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "idUtilisateur": "user-uuid",
    "montant": 5000,
    "devise": "XOF",
    "codePin": "1234",
    "modePaiement": "telephone",
    "numeroTelephone": "+221772345678"
  }'
```

#### Effectuer un transfert
```bash
curl -X POST http://localhost:8000/api/transfert/effectuer-transfert \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "telephoneDestinataire": "+221771234567",
    "montant": 10000,
    "devise": "XOF",
    "codePin": "1234",
    "note": "Paiement loyer"
  }'
```

#### Consulter le solde
```bash
curl -X POST http://localhost:8000/api/portefeuille/user-uuid/solde \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 📚 API Documentation

### Swagger UI
Accédez à la documentation interactive :
```
http://localhost:8000/api/documentation
```

### Endpoints principaux

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/auth/creercompte` | POST | Créer un compte utilisateur |
| `/api/auth/connexion` | POST | Connexion avec OTP |
| `/api/auth/verification-otp` | POST | Vérifier le code OTP |
| `/api/compte` | GET | Dashboard utilisateur |
| `/api/paiement/effectuer-paiement` | POST | Effectuer un paiement |
| `/api/transfert/effectuer-transfert` | POST | Effectuer un transfert |
| `/api/portefeuille/{id}/solde` | POST | Consulter le solde |
| `/api/portefeuille/{id}/transactions` | POST | Historique des transactions |

### Codes de réponse

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 400 | Données invalides |
| 401 | Non autorisé / OTP invalide |
| 404 | Ressource non trouvée |
| 409 | Conflit (utilisateur existe) |
| 422 | Erreur de validation |
| 500 | Erreur serveur |

## 🧪 Tests

### Tests unitaires
```bash
php artisan test
```

### Tests spécifiques
```bash
# Tests d'authentification
php artisan test --filter AuthServiceTest

# Tests de paiement
php artisan test --filter PaiementServiceTest

# Tests de transfert
php artisan test --filter TransfertServiceTest
```

### Tests manuels (avec seeders)
```bash
# Peupler la base avec des données de test
php artisan db:seed

# Utilisateur de test : +221771411251 (PIN: 1234)
# Solde initial : 50,000 FCFA
```

## 🔒 Sécurité

### Mesures de sécurité implémentées

- **🔐 Authentification JWT** : Tokens stateless avec expiration
- **📱 OTP SMS** : Vérification à deux facteurs obligatoire
- **🛡️ Rate Limiting** : Protection contre les attaques par déni de service
- **🔒 Hashage bcrypt** : Mots de passe sécurisés
- **✅ Validation stricte** : CNI, numéros de téléphone, montants
- **🚫 Protection XSS** : Sanitisation des entrées
- **🔐 CORS** : Contrôle des origines autorisées
- **📊 Logs de sécurité** : Traçabilité des actions sensibles

### Bonnes pratiques
- ✅ **Principe du moindre privilège**
- ✅ **Fail-safe defaults**
- ✅ **Defense in depth**
- ✅ **Secure by design**

## 📊 Base de Données

### Schéma principal

```sql
-- Utilisateurs
utilisateurs (id, numero_telephone, prenom, nom, email, numero_cni, statut_kyc, ...)

-- Authentification
authentifications (id, id_utilisateur, jeton_acces, jeton_rafraichissement, ...)

-- Portefeuilles
portefeuilles (id, id_utilisateur, solde, devise, ...)

-- Transactions
transactions (id, id_portefeuille, type, montant, statut, reference, ...)

-- Transferts
transferts (id, id_transaction, id_expediteur, id_destinataire, ...)

-- Paiements
paiements (id, id_transaction, id_marchand, mode_paiement, ...)

-- Marchands
marchands (id, nom, numero_telephone, categorie, accepte_qr, ...)

-- QR Codes
qr_codes (id, id_marchand, donnees, montant, date_expiration, ...)

-- Codes de paiement
code_paiements (id, id_marchand, code, montant, date_expiration, ...)

-- Orange Money (référence)
orange_money (id, numero_telephone, prenom, nom, solde, statut_compte, ...)
```

### Index et optimisations
- ✅ Index sur `numero_telephone` (recherche rapide)
- ✅ Index sur `id_utilisateur` (relations optimisées)
- ✅ Index composites pour les filtres fréquents
- ✅ Contraintes de clés étrangères

## 🚀 Déploiement

### Préparation pour la production

1. **Optimisation Laravel**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

2. **Variables d'environnement**
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com
```

3. **Serveur web (Nginx)**
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/ompay/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

4. **SSL/TLS**
```bash
# Let's Encrypt
certbot --nginx -d votre-domaine.com
```

5. **Monitoring**
```bash
# Laravel Telescope (dev)
php artisan telescope:install
php artisan migrate

# Logs
tail -f storage/logs/laravel.log
```

### Déploiement Docker (optionnel)

```dockerfile
# Dockerfile
FROM php:8.4-fpm-alpine

# Installation des dépendances système
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copie du code
COPY . /var/www/html
WORKDIR /var/www/html

# Installation des dépendances
RUN composer install --optimize-autoloader --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage

EXPOSE 9000
CMD ["php-fpm"]
```

## 🤝 Contribution

### Processus de contribution

1. **Fork** le projet
2. **Créer** une branche feature (`git checkout -b feature/AmazingFeature`)
3. **Commit** vos changements (`git commit -m 'Add some AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrir** une Pull Request

### Standards de code

- ✅ **PSR-12** : Standard PHP
- ✅ **Laravel conventions** : Nommage, structure
- ✅ **Tests unitaires** : Couverture > 80%
- ✅ **Documentation** : Code et API documentés

### Tests avant commit
```bash
# Linting
./vendor/bin/phpcs

# Tests
php artisan test

# Analyse statique
./vendor/bin/phpstan analyse
```


---

