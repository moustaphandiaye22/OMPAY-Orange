<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Utilisateur;

try {
    $u = Utilisateur::create([
        'numero_telephone' => '+221771000001',
        'prenom' => 'Tmp',
        'nom' => 'User',
        'email' => 'tmp.user@example.com',
        'code_pin' => '0000',
        'numero_cni' => '1111111111111'
    ]);
    echo "Created: ".json_encode($u->toArray())."\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
