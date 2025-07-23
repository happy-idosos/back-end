#  Happy Idosos - Backend

APIs desenvolvidas em PHP procedural com MySQLi para cadastro e login de dois tipos de usuários: **voluntários** (CPF) e **asilos** (CNPJ).


##  Funcionalidades já implementadas

- API de Cadastro (`/api-cadastro/cadastrar-usuario.php`)
-  API de Login (`/api-login/login.php`)
-  Validação de CPF e CNPJ
-  Armazenamento seguro de senha (`password_hash`)
-  Redirecionamento após login e cadastro
-  Reconhecimento de tipo de usuário no frontend


##  Próximas tarefas

-  Criar API de vídeos (upload e visualização)
-  Criar API de eventos (criação, visualização e associação com usuários)


##  Estrutura dos arquivos

/api-cadastro/cadastrar-usuario.php → Cadastro de usuário
/api-login/login.php → Login de usuário
/funcoes/validadores.php → Funções de validação (CPF, CNPJ)
/conexao.php → Conexão com o banco de dados


##  Banco de dados: Tabela `usuarios`

  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('voluntario', 'asilos') NOT NULL,
  documento VARCHAR(20) NOT NULL

## Integração com Frontend
Cadastro
Envio de JSON para a rota de cadastro via fetch.

Em caso de sucesso, redireciona para login.html.

Login
Envio de JSON com email e senha.

Em caso de sucesso, dados do usuário são salvos em localStorage.

Frontend usa esse dado para identificar permissões.

## Melhorias futuras
Migrar localStorage para sessões seguras com PHP ou JWT

Implementar controle de acesso em páginas protegidas

Proteção contra brute force e CSRF

Logs de acesso para auditoria


