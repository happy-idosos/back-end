<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Registro de usuário ou asilo
     */
    public function register(Request $request)
    {
        // Validação básica
        $request->validate([
            'tipo' => ['required', Rule::in(['voluntario', 'asilo'])],
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'senha' => 'required|string|confirmed|min:6',
        ]);

        $tipo = $request->tipo;

        // Validação específica por tipo
        if ($tipo === 'voluntario') {
            $request->validate([
                'cpf' => ['required', 'unique:users,cpf'],
                'data_nascimento' => ['required', 'date'],
            ]);
            $cpf = $request->cpf;
            $cnpj = null;
            $dataNascimento = $request->data_nascimento;
        } else { // asilo
            $request->validate([
                'cnpj' => ['required', 'unique:users,cnpj'],
            ]);
            $cpf = null;
            $cnpj = $request->cnpj;
            $dataNascimento = null;
        }

        // Criação do usuário
        $user = User::create([
            'tipo' => $tipo,
            'nome' => $request->nome,
            'cpf' => $cpf,
            'cnpj' => $cnpj,
            'telefone' => $request->telefone ?? null,
            'data_nascimento' => $dataNascimento,
            'email' => $request->email,
            'senha' => Hash::make($request->senha),
        ]);

        return response()->json([
            'message' => ucfirst($tipo) . ' cadastrado com sucesso',
            'user' => $user
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->senha, $user->senha)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        // Criação de token via Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => $user,
            'token' => $token
        ]);
    }
}
