<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparation_technicien', function (Blueprint $table) {
            $table->string('reparation_id');
            $table->foreignId('technicien_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->primary(['reparation_id', 'technicien_id']);
            $table->foreign('reparation_id')->references('id')->on('reparations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparation_technicien');
    }
};
