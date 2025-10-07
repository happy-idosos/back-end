
# 🧓 Happy Idosos - Backend

## Backend desenvolvido em **PHP em POO COM PDO**, fornecendo APIs REST para cadastro, autenticação e localização de asilos. O sistema diferencia dois tipos de usuários: **voluntários (CPF)** e **asilos (CNPJ)**.

Crie o arquivo .env:

```
DB_HOST=localhost
DB_NAME=happy_idosos
DB_USER=root
DB_PASS=
JWT_SECRET=57VZok7RCkLdi0IGV4iDLRHmOcZU3Sed0GMjr5egCZc=
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=pedromedeirosetec02@gmail.com
SMTP_PASSWORD="qpwj ekmy jsia afnk"
SMTP_FROM_NAME="Happy Idosos"
SMTP_FROM_EMAIL=pedromedeirosetec02@gmail.com
SMTP_SECURE=tls


JWT_SECRET=57VZok7RCkLdi0IGV4iDLRHmOcZU3Sed0GMjr5egCZc=

```

# Baixe o composer.phar
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Use o composer localmente
php composer.phar install

cd api-php

composer install


# Happy Idosos API - Sistema Refatorado

API completa com autenticação JWT e sistema de vídeos funcional.

## Mudanças Principais

### 1. Autenticação JWT Implementada
- Middleware `AuthMiddleware.php` criado para validar tokens
- Rotas protegidas agora exigem token no header `Authorization: Bearer {token}`
- ID do usuário extraído do token (não mais aceito do body)

### 2. Sistema de Vídeos Refatorado
- Upload funcional com validação de tipo e tamanho
- Suporte para MP4, WEBM, OGG, MOV
- Limite de 100MB por vídeo
- Associação automática com usuário/asilo autenticado
- Deleção apenas pelo autor

### 3. Sistema de Eventos Seguro
- Criar evento: apenas asilos autenticados
- Participar: apenas usuários autenticados
- Listar participantes: apenas asilo dono do evento

## Como Usar

### 1. Login
\`\`\`bash
POST /api/login
Content-Type: application/json

{
  "email": "usuario@email.com",
  "senha": "senha123"
}

# Resposta:
{
  "status": 200,
  "data": {
    "id": 1,
    "nome": "João Silva",
    "email": "usuario@email.com",
    "tipo": "usuario",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
\`\`\`

### 2. Participar de Evento (CORRIGIDO)
\`\`\`bash
POST /api/eventos/participar
Authorization: Bearer {seu_token_aqui}
Content-Type: application/json

{
  "id_evento": 5
}

# Resposta de sucesso:
{
  "status": 201,
  "message": "Inscrição realizada com sucesso"
}
\`\`\`

### 3. Upload de Vídeo
\`\`\`bash
POST /api/videos
Authorization: Bearer {seu_token_aqui}
Content-Type: multipart/form-data

video: [arquivo de vídeo]
titulo: "Meu vídeo"
descricao: "Descrição do vídeo"

# Resposta:
{
  "status": 201,
  "message": "Vídeo enviado com sucesso",
  "data": {
    "id_midia": 10,
    "nome": "Meu vídeo",
    "url": "uploads/videos/video_123.mp4",
    "enviado_por": "João Silva"
  }
}
\`\`\`

### 4. Criar Evento (Apenas Asilos)
\`\`\`bash
POST /api/eventos/criar
Authorization: Bearer {token_do_asilo}
Content-Type: application/json

{
  "titulo": "Festa Junina",
  "descricao": "Venha participar!",
  "data_evento": "2025-06-15"
}
\`\`\`

## Rotas Públicas (Sem Token)
- `GET /api/eventos` - Listar todos eventos
- `GET /api/videos` - Listar todos vídeos
- `GET /api/usuarios` - Listar usuários
- `GET /api/asilos` - Listar asilos
- `POST /api/login` - Login
- `POST /api/cadastro/usuario` - Cadastro de usuário
- `POST /api/cadastro/asilo` - Cadastro de asilo

## Rotas Protegidas (Requerem Token)
- `POST /api/eventos/criar` - Criar evento (asilo)
- `POST /api/eventos/participar` - Participar de evento (usuário)
- `DELETE /api/eventos/participar` - Cancelar participação (usuário)
- `GET /api/eventos/meus` - Meus eventos (usuário)
- `GET /api/eventos/:id/participantes` - Ver participantes (asilo)
- `POST /api/videos` - Upload de vídeo (qualquer autenticado)
- `DELETE /api/videos/:id` - Deletar vídeo (autor)

## Estrutura de Arquivos
\`\`\`
api-php/
├── middleware/
│   └── AuthMiddleware.php          # Validação JWT
├── controllers/
│   ├── LoginController.php         # Login e geração de token
│   ├── EventoController.php        # CRUD de eventos
│   ├── ParticipacaoController.php  # Participações em eventos
│   └── VideoController.php         # Upload e listagem de vídeos
├── routes/
│   └── rotas.php                   # Definição de rotas
└── uploads/
    └── videos/                     # Vídeos enviados
\`\`\`

## Segurança
- Tokens JWT com validade de 7 dias
- Validação de tipo MIME real dos arquivos
- Proteção contra upload de arquivos maliciosos
- Verificação de permissões em todas operações sensíveis
- IDs extraídos do token (não confiamos no body)
