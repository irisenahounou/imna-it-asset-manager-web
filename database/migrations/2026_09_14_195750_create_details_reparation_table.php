<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('details_reparation', function (Blueprint $table) {
            $table->id();
            $table->string('reparation_id');
            $table->unsignedBigInteger('produit_id');
            $table->integer('quantite')->default(1);
            $table->text('commentaire')->nullable();
            
            $table->foreign('reparation_id')->references('id')->on('reparations')->onDelete('cascade');
            $table->foreign('produit_id')->references('id_composant')->on('produits')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('details_reparation');
    }
};
