
# 🧓 Happy Idosos - Backend

## Backend desenvolvido em **PHP em POO COM PDO**, fornecendo APIs REST para cadastro, autenticação e localização de asilos. O sistema diferencia dois tipos de usuários: **voluntários (CPF)** e **asilos (CNPJ)**.

## Happy Idosos API
# Bem-vindo à API Happy Idosos! Este projeto é uma API RESTful desenvolvida em PHP para gerenciar o cadastro de usuários e asilos.

Visão Geral
A API Happy Idosos permite o registro de dois tipos de entidades:

Usuários: Indivíduos que podem interagir com a plataforma.
Asilos: Instituições que oferecem serviços para idosos.
A API é construída com foco na simplicidade e na separação de responsabilidades, utilizando controladores para lidar com a lógica de negócio e validadores para garantir a integridade dos dados.

Estrutura do Projeto
```
.
├── config/
│   ├── connection.php        # Configuração de conexão com o banco de dados
│   └── cors.php              # Configuração de Cross-Origin Resource Sharing (CORS)
├── controllers/
│   ├── CadastroAsiloController.php   # Lógica para cadastro de asilos
│   └── CadastroUsuarioController.php # Lógica para cadastro de usuários
├── routes/
│   └── rotas.php             # Definição das rotas da API
├── utils/
│   └── validators.php        # Funções de validação (CPF, CNPJ)
├── .env                      # Variáveis de ambiente (ex: credenciais do banco de dados)
├── .gitignore                # Arquivos e diretórios a serem ignorados pelo Git
├── .htaccess                 # Configurações do servidor Apache (para reescrita de URL)
├── happy_idosos.sql          # Script SQL para criação do banco de dados
└── index.php                 # Ponto de entrada da aplicação
```

Crie o arquivo .env:

```
DB_HOST=localhost
DB_NAME=happy_idosos
DB_USER=root
DB_PASS=

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=pedromedeirosetec02@gmail.com
SMTP_PASSWORD=qpwj ekmy jsia afnk
SMTP_FROM_NAME=Happy Idosos
SMTP_FROM_EMAIL=pedromedeirosetec02@gmail.com
```