<?php

function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

function validarCNPJ($cnpj)
{
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;

    $tamanho = [12, 13];
    $multiplicadores = [
        [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
    ];

    for ($i = 0; $i < 2; $i++) {
        $soma = 0;
        for ($j = 0; $j < $tamanho[$i]; $j++) {
            $soma += $cnpj[$j] * $multiplicadores[$i][$j];
        }
        $digito = ($soma % 11 < 2) ? 0 : 11 - ($soma % 11);
        if ($cnpj[$tamanho[$i]] != $digito) return false;
    }
    return true;
}

function validarTelefone($telefone)
{
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    return preg_match('/^\d{10,11}$/', $telefone);
}

function validarSenha($senha)
{
    if (strlen($senha) < 8) return false;
    if (!preg_match('/[A-Z]/', $senha)) return false;
    if (!preg_match('/[a-z]/', $senha)) return false;
    if (!preg_match('/[0-9]/', $senha)) return false;
    if (!preg_match('/[\W]/', $senha)) return false;
    return true;
}

function validarNome($nome)
{
    return preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $nome);
}

function validarEmail($email) {
    // Remove espaços em branco desnecessários no início e fim
    $email = trim($email);

    // Usa filter_var para validar o formato do e-mail
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    } else {
        return false;
    }
}
function validarData($data)
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}   