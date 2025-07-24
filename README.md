
# 🧓 Happy Idosos - Backend

Backend desenvolvido em **PHP procedural com MySQLi**, fornecendo APIs REST para cadastro, autenticação e localização de asilos. O sistema diferencia dois tipos de usuários: **voluntários (CPF)** e **asilos (CNPJ)**.

---

## 📁 Estrutura do Repositório

```
.
├── auth/
│   ├── login.php               # API de login
│   └── logout.php              # API de logout
├── busca/
│   └── buscar-asilos.php       # API para localizar asilos próximos
├── cadastro/
│   └── cadastrar-usuario.php   # API de cadastro de usuários
├── config/
│   └── conexao.php             # Configuração e conexão ao banco de dados
├── funcoes/
│   └── validadores.php         # Validação de CPF/CNPJ
├── public/
│   ├── buscar-asilos.html      # Interface de busca
│   ├── cadastro.html           # Tela de cadastro
│   ├── index.html              # Tela inicial após login
│   ├── login.html              # Tela de login
├── sql/
│   └── banco.sql               # Script SQL para criação do banco
```

---

## 🔐 APIs Implementadas

### `auth/login.php`
- **Método:** `POST`
- **Função:** Autentica o usuário via `email` e `senha`.
- **Retorno:** JSON com dados do usuário autenticado.

### `auth/logout.php`
- **Método:** `GET`
- **Função:** Finaliza a sessão e redireciona para o login.

### `busca/buscar-asilos.php`
- **Método:** `POST`
- **Função:** Recebe `latitude` e `longitude` e retorna asilos ordenados pela distância.

### `cadastro/cadastrar-usuario.php`
- **Método:** `POST`
- **Função:** Cadastra um novo usuário no sistema com validação de dados (incluindo CPF ou CNPJ).

---

## 🔗 Integração Frontend

O frontend realiza requisições HTTP utilizando `fetch`. Exemplos:

### Login
```javascript
const resposta = await fetch("http://localhost/api-php/auth/login.php", {
  method: "POST",
  body: JSON.stringify({ email, senha }),
  headers: { "Content-Type": "application/json" }
});
```

### Cadastro
```javascript
const response = await fetch("../cadastro/cadastrar-usuario.php", {
  method: "POST",
  body: JSON.stringify(dadosUsuario),
  headers: { "Content-Type": "application/json" }
});
```

### Busca de Asilos
```javascript
fetch('../busca/buscar-asilos.php', {
  method: 'POST',
  body: JSON.stringify({ latitude, longitude }),
  headers: { 'Content-Type': 'application/json' }
});
```

---

## 🧾 Estrutura do Banco de Dados

```sql
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('voluntario', 'asilos') NOT NULL,
  documento VARCHAR(20) NOT NULL,
  endereco TEXT,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🚀 Melhorias Futuras

- ✅ Validações robustas no frontend
- ✅ Mensagens de erro mais específicas nas APIs
- 🔐 Proteção contra SQL Injection, XSS, CSRF e brute force
- 🧪 Testes automatizados para backend e frontend
- 🧭 Integração com APIs de geolocalização mais precisas
- 📄 Documentação com Swagger/Postman
- 💬 Feedback visual ao usuário em operações (carregamento, erros, sucessos)
- 🧾 Migração para sessões seguras com PHP ou tokens JWT
