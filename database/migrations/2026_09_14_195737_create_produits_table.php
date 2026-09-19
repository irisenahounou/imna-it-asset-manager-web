<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id('id_composant');
            $table->string('nom_composant');
            $table->string('type_composant');
            $table->integer('quantite_stock')->default(0);
            $table->integer('seuil_alerte')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};