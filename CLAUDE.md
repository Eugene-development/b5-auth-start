# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

B5 Auth 2 - сервер аутентификации для системы Bonus5. Построен на **Laravel 12** с использованием **Laravel Sanctum** для token-based аутентификации. Предоставляет REST API endpoints для регистрации, логина, сброса пароля и верификации email.

Работает на порту **8001** по умолчанию.

## Commands

### Development
```bash
php artisan serve --port=8001   # Запуск dev сервера (порт 8001)
npm run dev                     # Запуск Vite для фронтенд ассетов
```

### Database
```bash
php artisan migrate            # Выполнить миграции
php artisan migrate:fresh      # Пересоздать БД
php artisan migrate:rollback   # Откатить последнюю миграцию
php artisan db:seed            # Запустить seeders
```

### Testing & Code Quality
```bash
php artisan test               # Запуск тестов (PHPUnit)
./vendor/bin/pint              # Форматирование кода (Laravel Pint)
php artisan pail               # Мониторинг логов в реальном времени
```

### Artisan Commands
```bash
php artisan tinker             # REPL для Laravel
php artisan route:list         # Список всех роутов
php artisan config:clear       # Очистить кэш конфигурации
php artisan cache:clear        # Очистить application cache
```

## Architecture

### Authentication Flow

Использует **Laravel Sanctum** для SPA аутентификации:
1. Клиент получает CSRF cookie через `/sanctum/csrf-cookie`
2. Отправляет credentials на `/api/login` или `/api/register` с CSRF token
3. Получает токен аутентификации
4. Использует токен в заголовке `Authorization: Bearer {token}` для защищенных endpoints

### API Endpoints

Все routes определены в `routes/api.php`:

#### Public Endpoints
- `POST /api/login` - вход пользователя
- `POST /api/register` - регистрация нового пользователя
- `POST /api/forgot-password` - запрос на сброс пароля (rate limit: 3/min)
- `POST /api/reset-password` - сброс пароля по токену
- `GET /api/email/verify/{id}/{hash}` - верификация email по ссылке

#### Protected Endpoints (require `auth:sanctum` middleware)
- `POST /api/logout` - выход пользователя
- `GET /api/user` - получение данных текущего пользователя
- `POST /api/email/verification-notification` - повторная отправка письма верификации (rate limit: 6/min)

### Application Structure

```
app/
├── Http/
│   └── Controllers/
│       └── AuthController.php    # Все authentication endpoints
├── Models/
│   └── User.php                  # Eloquent User model с Sanctum traits
├── Notifications/
│   └── CustomVerifyEmail.php     # Кастомная email verification notification
└── Providers/
    └── AppServiceProvider.php    # Service provider configuration
```

### User Model

`app/Models/User.php`:
- Использует `HasApiTokens` trait от Laravel Sanctum
- Использует `MustVerifyEmail` для email verification
- Поля: id, name, email, email_verified_at, password, remember_token, timestamps

### Email Verification

Кастомная notification для email verification в `app/Notifications/CustomVerifyEmail.php` с возможностью настройки frontend URL для verification link.

### Configuration

Environment переменные в `.env`:
```
APP_URL=http://localhost:8001
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=b5_auth_db
DB_USERNAME=root
DB_PASSWORD=

# Sanctum configuration
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:5174
SESSION_DOMAIN=localhost

# Frontend URLs for email verification
FRONTEND_URL=http://localhost:5173
ADMIN_URL=http://localhost:5174

# Mail configuration (Mailgun)
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-mailgun-domain
MAILGUN_SECRET=your-mailgun-secret
```

**Важно**: `SANCTUM_STATEFUL_DOMAINS` должен включать все frontend домены для корректной работы CSRF защиты.

### CORS Configuration

CORS настроен в `config/cors.php` для работы с frontend приложениями (b5-agent, b5-admin). Поддерживаемые origins указываются в environment variables.

### Rate Limiting

Endpoints с ограничением частоты запросов:
- `/api/forgot-password`: 3 запроса в минуту
- `/api/email/verification-notification`: 6 запросов в минуту

## Key Technologies

- **Laravel 12** - PHP фреймворк
- **Laravel Sanctum 4** - SPA/API token authentication
- **MySQL** - база данных
- **Mailgun** - email service provider для отправки писем
- **Laravel Vite Plugin** - для фронтенд ассетов

## Security Features

- **CSRF Protection** - через Sanctum для SPA authentication
- **Rate Limiting** - на чувствительных endpoints (password reset, email verification)
- **Email Verification** - обязательная верификация email для новых пользователей
- **Password Hashing** - bcrypt/argon2 для хранения паролей
- **Token-based Auth** - secure API tokens через Sanctum

## Docker

Production deployment:
- `Dockerfile.production` - Docker image configuration
- `entrypoint.sh` - Docker entrypoint script

## Development Workflow

1. Изменяйте/добавляйте endpoints в `routes/api.php`
2. Реализуйте логику в `app/Http/Controllers/AuthController.php`
3. Создавайте/обновляйте модели в `app/Models/`
4. Создавайте миграции: `php artisan make:migration`
5. Тестируйте endpoints через Postman/curl или frontend приложения

## Integration with Frontend Apps

Фронтенд приложения (b5-agent, b5-admin) используют этот сервис для:
- Регистрации и авторизации пользователей
- Получения данных текущего пользователя
- Верификации email
- Сброса паролей

Убедитесь, что CORS и Sanctum правильно настроены для взаимодействия с фронтендом.
