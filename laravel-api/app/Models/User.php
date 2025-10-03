<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Define a tabela correta
    protected $table = 'usuarios';

protected $fillable = [
    'tipo', 'nome', 'cpf', 'telefone', 'data_nascimento', 'email', 'senha'
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
}
