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
