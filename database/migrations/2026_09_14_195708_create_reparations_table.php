<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->text('rapport_technique')->nullable();
            $table->foreignId('panne_id')->unique()->constrained('pannes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparations');
    }
};