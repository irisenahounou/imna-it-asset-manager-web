<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pannes', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->dateTime('date_declaration')->useCurrent();
            $table->string('statut')->default('En attente');
            $table->string('photo')->nullable();
            $table->enum('type_panne', ['CORRECTIVE', 'PREVENTIVE']);
            $table->integer('frequence_jour')->nullable();
            $table->date('prochaine_reparation')->nullable();
            $table->foreignId('materiel_id')->constrained('materiels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pannes');
    }
};
