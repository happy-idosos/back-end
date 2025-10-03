<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asilo extends Model
{
    use HasFactory;

    protected $table = 'asilos'; // tabela correspondente

    

    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'endereco',
        'cidade',
        'estado',
        'email',
        'senha',
        'latitude',
        'longitude'
    ];

    protected $hidden = ['senha']; // não retornar senha no JSON
}
