Happy Idosos - README
Estrutura do Repositório

Run
Copy code
.
├── .git/                     # Diretório do Git
├── auth/                     # Scripts de autenticação
│   ├── login.php             # API para login de usuários
│   └── logout.php            # API para logout de usuários
├── busca/                    # Scripts para busca de asilos
│   └── buscar-asilos.php     # API para buscar asilos próximos
├── cadastro/                 # Scripts para cadastro de usuários
│   └── cadastrar-usuario.php  # API para cadastrar novos usuários
├── config/                   # Configurações do banco de dados
│   └── conexao.php           # Conexão com o banco de dados
├── funcoes/                  # Funções auxiliares
│   └── validadores.php       # Funções para validação de CPF e CNPJ
├── public/                   # Frontend da aplicação
│   ├── buscar-asilos.html    # Página para exibir asilos próximos
│   ├── cadastro.html         # Página de cadastro de usuários
│   ├── index.html            # Página inicial após login
│   ├── login.html            # Página de login
│   └── cadastro.html         # Página de cadastro
└── sql/                      # Scripts SQL
    └── banco.sql             # Script para criação do banco de dados e tabelas
Descrição das APIs
auth/login.php

Método: POST
Descrição: Autentica um usuário com email e senha. Retorna os dados do usuário se as credenciais forem válidas.
auth/logout.php

Método: GET
Descrição: Finaliza a sessão do usuário e redireciona para a página de login.
busca/buscar-asilos.php

Método: POST
Descrição: Recebe a latitude e longitude do usuário e retorna uma lista de asilos próximos, ordenados pela distância.
cadastro/cadastrar-usuario.php

Método: POST
Descrição: Cadastra um novo usuário (voluntário ou asilo) no sistema, realizando validações de dados.
Conexão do Frontend com as APIs
O frontend se conecta às APIs utilizando fetch para realizar requisições HTTP. Aqui estão alguns exemplos de como isso é feito:

Exemplo de Login
javascript
5 lines
Click to expand
const resposta = await fetch("http://localhost/api-php/auth/login.php", {
method: "POST",
...
Exemplo de Cadastro
javascript
5 lines
Click to expand
const response = await fetch("../cadastro/cadastrar-usuario.php", {
method: "POST",
...
Exemplo de Busca de Asilos
javascript
9 lines
Click to close
fetch('../busca/buscar-asilos.php', {
method: 'POST',
...
Estrutura do Banco de Dados
Para que as APIs funcionem corretamente, o banco de dados deve ter a seguinte estrutura:

Tabela: usuarios
id: INT, AUTO_INCREMENT, PRIMARY KEY
nome: VARCHAR(100), NOT NULL
email: VARCHAR(100), NOT NULL, UNIQUE
senha: VARCHAR(255), NOT NULL
tipo: ENUM ('voluntario', 'asilos'), NOT NULL
documento: VARCHAR(20), NOT NULL (CPF ou CNPJ)
endereco: TEXT (opcional, apenas para asilos)
latitude: DECIMAL(10, 8) (opcional, apenas para asilos)
longitude: DECIMAL(11, 8) (opcional, apenas para asilos)
criado_em: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
Sugestões de Melhoria
Validação de Dados no Frontend:

Implementar validações mais robustas no frontend para evitar requisições desnecessárias ao backend.
Tratamento de Erros:

Melhorar o tratamento de erros nas APIs, retornando mensagens mais detalhadas e específicas.
Documentação das APIs:

Criar uma documentação mais detalhada das APIs utilizando ferramentas como Swagger ou Postman.
Segurança:

Implementar medidas de segurança, como proteção contra SQL Injection e XSS, além de utilizar HTTPS para todas as requisições.
Testes Automatizados:

Adicionar testes automatizados para as APIs e o frontend, garantindo que as funcionalidades estejam sempre funcionando como esperado.
Melhorar a Experiência do Usuário:

Adicionar feedback visual ao usuário durante o carregamento de dados e ao realizar ações, como cadastro e login.
Geolocalização:

Considerar o uso de uma API de geolocalização mais robusta para garantir a precisão das coordenadas.
