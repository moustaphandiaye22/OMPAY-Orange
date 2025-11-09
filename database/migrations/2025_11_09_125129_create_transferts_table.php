<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transaction')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('id_expediteur')->constrained('utilisateurs');
            $table->foreignId('id_destinataire')->constrained('destinataires');
            $table->string('nom_destinataire', 100);
            $table->text('note')->nullable();
            $table->timestamp('date_expiration')->nullable();
            $table->timestamps();

            $table->index(['id_expediteur', 'id_destinataire']);
            $table->index('date_expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
