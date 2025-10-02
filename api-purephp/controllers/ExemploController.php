<?php
class ExemploController
{
    public function index()
    {
        echo json_encode(["mensagem" => "API funcionando!"]);
    }
}
