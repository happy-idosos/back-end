<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Define a tabela correta
    protected $table = 'usuarios';

    protected $fillable = [
        'tipo', 'nome', 'cpf', 'cnpj', 'telefone', 'data_nascimento', 'email', 'senha'
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    // Mutator para hashear a senha automaticamente
    public function setSenhaAttribute($value)
    {
        $this->attributes['senha'] = bcrypt($value);
    }

    /**
     * Sobrescreve o método getAuthPassword para usar o campo 'senha'
     */
    public function getAuthPassword()
    {
        return $this->senha;
    }
}
