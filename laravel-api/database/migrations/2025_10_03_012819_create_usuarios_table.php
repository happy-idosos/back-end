<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('usuarios', function (Blueprint $table) {
    $table->id();
    $table->string('tipo'); // voluntario ou asilo
    $table->string('nome');
    $table->string('cpf')->unique();
    $table->string('telefone')->nullable();
    $table->date('data_nascimento')->nullable();
    $table->string('email')->unique();
    $table->string('senha');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
