
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
Copie o arquivo .env.example (se existir, caso contrário, crie um) e preencha com suas credenciais de banco de dados.

```
DB_HOST=localhost
DB_NAME=happy_idosos
DB_USER=root
DB_PASS=
```
Endpoints da API
A API expõe os seguintes endpoints:

GET /

Descrição: Verifica se a API está funcionando.
Resposta: {"message": "API Happy Idosos está funcionando!"}
POST /cadastro/usuario

Descrição: Cadastra um novo usuário.
Corpo da Requisição (JSON):
{
"cpf": "123.456.789-00",
...
Respostas:
201 Created: {"status": 201, "message": "Usuário cadastrado com sucesso."}
400 Bad Request: {"status": 400, "message": "Dados obrigatórios não preenchidos."} ou {"status": 400, "message": "CPF inválido."}
500 Internal Server Error: {"status": 500, "message": "Erro ao cadastrar usuário."}
POST /cadastro/asilo

Descrição: Cadastra um novo asilo.
Corpo da Requisição (JSON):
{
"cnpj": "11.222.333/0001-44",
...
Respostas:
201 Created: {"status": 201, "message": "Asilo cadastrado com sucesso."}
400 Bad Request: {"status": 400, "message": "Dados obrigatórios não preenchidos."} ou {"status": 400, "message": "CNPJ inválido."}
500 Internal Server Error: {"status": 500, "message": "Erro ao cadastrar asilo."}

Validação de Entrada Mais Abrangente:
Sanitização: Além de validar, sanitize os dados de entrada para prevenir ataques como XSS (Cross-Site Scripting) e SQL Injection. Use filter_var() com filtros apropriados ou prepare statements de forma mais rigorosa.
Validação de Email: Adicione validação de formato de email (filter_var($email, FILTER_VALIDATE_EMAIL)).
Validação de Telefone: Embora não seja estritamente necessário para a lógica de negócio, validar o formato do telefone pode ser útil.
Comprimento Mínimo/Máximo: Valide o comprimento mínimo e máximo para campos como nome, email e senha.
Complexidade da Senha: Implemente regras de complexidade para senhas (mínimo de caracteres, letras maiúsculas/minúsculas, números, símbolos).
Prevenção de Duplicidade:
Antes de inserir um novo usuário ou asilo, verifique se o CPF/CNPJ ou email já existem no banco de dados. Isso evita registros duplicados e fornece feedback mais claro ao usuário.
Rate Limiting:
Implemente um sistema de rate limiting para proteger seus endpoints contra ataques de força bruta ou uso excessivo.
HTTPS:
Sempre use HTTPS em produção para criptografar a comunicação entre o cliente e a API.
CORS em Produção:
Em produção, restrinja Access-Control-Allow-Origin para domínios específicos em vez de *.
