<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->string('numero_serie')->unique();
            $table->string('nom_equipement');
            $table->string('type');
            $table->date('date_achat');
            $table->string('etat_operationnel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};
