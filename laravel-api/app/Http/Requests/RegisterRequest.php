<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Validators;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permite que qualquer usuário faça o registro
        return true;
    }

    public function rules(): array
    {
        $tipo = $this->input('tipo');

        if ($tipo === 'voluntario') {
            return [
                'tipo' => 'required|in:voluntario,asilo',
                'nome' => 'required|string|max:255',
                'cpf' => ['required', 'string', function($attribute, $value, $fail) {
                    if (!Validators::validarCPF($value)) {
                        $fail('CPF inválido.');
                    }
                }],
                'telefone' => 'nullable|string|max:20',
                'data_nascimento' => 'required|date',
                'email' => 'required|email|unique:usuarios,email',
                'senha' => 'required|string|min:6|confirmed',
            ];
        } elseif ($tipo === 'asilo') {
            return [
                'tipo' => 'required|in:voluntario,asilo',
                'nome' => 'required|string|max:255',
                'cnpj' => ['required', 'string', function($attribute, $value, $fail) {
                    if (!Validators::validarCNPJ($value)) {
                        $fail('CNPJ inválido.');
                    }
                }],
                'telefone' => 'nullable|string|max:20',
                'endereco' => 'required|string|max:255',
                'cidade' => 'required|string|max:100',
                'estado' => 'required|string|max:2',
                'email' => 'required|email|unique:asilos,email',
                'senha' => 'required|string|min:6|confirmed',
            ];
        }

        // Se tipo inválido
        return [];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'email' => 'O campo :attribute deve ser um e-mail válido.',
            'unique' => 'O :attribute já está cadastrado.',
            'senha.confirmed' => 'A confirmação da senha não coincide.',
        ];
    }
}
