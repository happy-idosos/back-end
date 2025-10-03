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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->string('cpf')->nullable()->unique()->after('tipo');
            }
            if (!Schema::hasColumn('users', 'cnpj')) {
                $table->string('cnpj')->nullable()->unique()->after('cpf');
            }
            if (!Schema::hasColumn('users', 'telefone')) {
                $table->string('telefone')->nullable()->after('cnpj');
            }
            if (!Schema::hasColumn('users', 'data_nascimento')) {
                $table->date('data_nascimento')->nullable()->after('telefone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cpf')) {
                $table->dropColumn('cpf');
            }
            if (Schema::hasColumn('users', 'cnpj')) {
                $table->dropColumn('cnpj');
            }
            if (Schema::hasColumn('users', 'telefone')) {
                $table->dropColumn('telefone');
            }
            if (Schema::hasColumn('users', 'data_nascimento')) {
                $table->dropColumn('data_nascimento');
            }
        });
    }
};
