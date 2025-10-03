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
        $request->validate([
            'tipo' => ['required', Rule::in(['voluntario', 'asilo'])],
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'senha' => 'required|string|confirmed|min:6',
        ]);

        $tipo = $request->tipo;

        $userData = [
            'tipo' => $tipo,
            'nome' => $request->nome,
            'telefone' => $request->telefone ?? null,
            'email' => $request->email,
            'senha' => $request->senha,
        ];

        if ($tipo === 'voluntario') {
            $request->validate([
                'cpf' => ['required', 'unique:usuarios,cpf'],
                'data_nascimento' => ['required', 'date'],
            ]);
            if (!$this->validarCpf($request->cpf)) {
                return response()->json(['message' => 'CPF inválido'], 422);
            }
            $userData['cpf'] = $request->cpf;
            $userData['data_nascimento'] = $request->data_nascimento;
        } else { // asilo
            $request->validate([
                'cnpj' => ['required', 'unique:usuarios,cnpj'],
            ]);
            if (!$this->validarCnpj($request->cnpj)) {
                return response()->json(['message' => 'CNPJ inválido'], 422);
            }
            $userData['cnpj'] = $request->cnpj;
        }

        $user = User::create($userData);

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

    private function validarCpf($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }

    private function validarCnpj($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) return false;
        for ($t = 12; $t < 14; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cnpj[$c] * (($t + 1 - $c) % 8 ?: 9);
            }
            if ($cnpj[$c] != ((10 * $d) % 11) % 10) return false;
        }
        return true;
    }
}
