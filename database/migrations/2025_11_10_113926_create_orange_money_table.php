<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        } catch (\Exception $e) {
            // Si l'extension existe déjà, on continue
        }

        if (Schema::hasTable('orange_money')) {
            Schema::dropIfExists('orange_money');
        }

           Schema::create('orange_money', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_telephone', 20)->unique();
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('email', 255)->nullable()->unique();
            $table->string('numero_cni', 50)->nullable()->unique();
            $table->enum('statut_compte', ['actif', 'suspendu', 'bloque'])->default('actif');
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('devise', 10)->default('FCFA');
            $table->timestamp('date_creation_compte')->useCurrent();
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orange_money');
    }
};
