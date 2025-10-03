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
    Schema::create('asilos', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->string('cnpj')->unique();
        $table->string('telefone')->nullable();
        $table->string('endereco');
        $table->string('cidade');
        $table->string('estado', 2);
        $table->string('email')->unique();
        $table->string('senha');
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asilos');
    }
};
