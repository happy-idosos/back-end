
# 🧓 Happy Idosos - Backend

## Backend desenvolvido em **LARAVEL**, fornecendo APIs REST para cadastro, autenticação e localização de asilos. O sistema diferencia dois tipos de usuários: **voluntários (CPF)** e **asilos (CNPJ)**.

Crie o arquivo .env:

```
APP_NAME="Happy Idosos"
APP_ENV=local
APP_KEY=base64:PWaqblD4Fo6hIdfkUKTof7SMntZtY6TiJ1H1Xdr80qI=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Banco de Dados (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=happy_idosos
DB_USERNAME=root
DB_PASSWORD=

# Sessão
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_STORE=database

# Email (Laravel usa mailer nativo, não PHPMailer puro)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=pedromedeirosetec02@gmail.com
MAIL_PASSWORD="qpwj ekmy jsia afnk"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=pedromedeirosetec02@gmail.com
MAIL_FROM_NAME="Happy Idosos"

```

cd laravel-api  

php artisan serve