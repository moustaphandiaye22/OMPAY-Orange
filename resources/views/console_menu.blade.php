{{-- Vue pour le menu principal de la console OMPAY --}}
@php
$menuItems = [
    '1' => 'Créer un nouveau compte',
    '2' => 'Finaliser l\'inscription',
    '3' => 'Se connecter',
    '4' => 'Se déconnecter',
    '5' => 'Voir le tableau de bord',
    '6' => 'Voir le profil',
    '7' => 'Changer le PIN',
    '8' => 'Consulter le solde',
    '9' => 'Historique des transactions',
    '10' => 'Détails de transaction',
    '11' => 'Transférer de l\'argent',
    '12' => 'Annuler un transfert',
    '13' => 'Effectuer un paiement',
    '0' => 'Quitter'
];
@endphp

╔══════════════════════════════════════════════════════════════╗
║                    🏦 OMPAY Orange Money                     ║
║                 Client Console - Version 1.0                 ║
║                                                              ║
║   Bienvenue dans l'application de gestion de compte Orange   ║
║   Money. Utilisez les options du menu pour naviguer.         ║
╚══════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│                        MENU PRINCIPAL                       │
├─────────────────────────────────────────────────────────────┤
@foreach($menuItems as $key => $item)
│  {{ $key }}. {{ $item }}@if($key !== '0'){{ str_repeat(' ', 48 - strlen($item) - strlen($key) - 2) }}│@else{{ str_repeat(' ', 48 - strlen($item) - strlen($key) - 2) }}│@endif
@endforeach
│                                                             │
│  0.  Quitter                                                │
└─────────────────────────────────────────────────────────────┘