# Изменение валидации регистрации: company_name теперь опциональное поле

## Дата изменения
3 января 2026

## Описание изменения

Поле `company_name` в процессе регистрации пользователя изменено с обязательного (`required`) на опциональное (`nullable`).

## Причина изменения

В форме регистрации на фронтенде (b5-agent) было убрано поле для ввода названия компании, но бэкенд продолжал требовать это поле, что приводило к ошибке валидации:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "company_name": ["The company name field is required."]
  }
}
```

## Внесенные изменения

### 1. AuthController.php (b5-auth-2)

**Файл:** `b5-auth-2/app/Http/Controllers/AuthController.php`

#### Изменена валидация в методе `register()`:

**Было:**
```php
'company_name' => 'required|string|min:2|max:255',
```

**Стало:**
```php
'company_name' => 'nullable|string|min:2|max:255',
'region' => 'nullable|string|max:255',
```

#### Изменена логика создания компании:

**Было:**
```php
// Create company
$company = Company::create([
    'name' => $request->company_name,
    'legal_name' => $request->company_name,
    'ban' => false,
    'is_active' => true,
    'status_id' => $companyStatusId,
]);

// Create user linked to company
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'registration_domain' => $registrationDomain,
    'status_id' => $userStatusId,
    'company_id' => $company->id,
]);
```

**Стало:**
```php
$company = null;

// Create company only if company_name is provided
if ($request->filled('company_name')) {
    $company = Company::create([
        'name' => $request->company_name,
        'legal_name' => $request->company_name,
        'ban' => false,
        'is_active' => true,
        'status_id' => $companyStatusId,
    ]);
}

// Create user linked to company (if exists)
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'registration_domain' => $registrationDomain,
    'status_id' => $userStatusId,
    'company_id' => $company?->id,
]);

// Add region if provided
if ($request->filled('region')) {
    $user->region = $request->region;
    $user->save();
}
```

## Поведение после изменений

### Регистрация БЕЗ company_name:
- Компания НЕ создается
- Пользователь создается с `company_id = null`
- Регистрация проходит успешно

### Регистрация С company_name:
- Компания создается
- Пользователь создается с привязкой к компании
- Регистрация проходит успешно (как раньше)

## Совместимость с базой данных

Поле `company_id` в таблице `users` уже было nullable согласно миграции:
- `2025_11_30_120000_add_company_id_to_users_table.php`

Поле `region` в таблице `users` также nullable согласно миграции:
- `2025_10_04_160044_add_fields_to_users_table.php`

## API Endpoint

**POST** `/api/register`

### Обязательные поля:
- `name` - имя пользователя
- `email` - email пользователя
- `password` - пароль (минимум 8 символов)
- `password_confirmation` - подтверждение пароля

### Опциональные поля:
- `company_name` - название компании (если указано, компания будет создана)
- `region` - регион пользователя
- `phone` - телефон пользователя

### Пример запроса БЕЗ company_name:
```json
{
  "name": "Иван Иванов",
  "email": "ivan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "region": "Москва",
  "phone": "+7 (900) 123-45-67"
}
```

### Пример успешного ответа:
```json
{
  "success": true,
  "user": {
    "id": "01JGXXX...",
    "name": "Иван Иванов",
    "email": "ivan@example.com",
    "company_id": null,
    "region": "Москва",
    "status_id": "...",
    "email_verified": false
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "message": "Registration successful. Please check your email to verify your account."
}
```

## Влияние на другие компоненты

### Фронтенд (b5-agent)
- Форма регистрации уже не содержит поле `company_name`
- Отправляет только: `name`, `email`, `password`, `password_confirmation`, `region` (опционально), `phone` (опционально)
- Изменения на фронтенде не требуются

### Тесты
- Существующие тесты в `b5-auth-2/tests/Feature/RegistrationCompanyTest.php` могут требовать обновления
- Тесты, проверяющие обязательность `company_name`, теперь не актуальны

## Рекомендации

1. Обновить тесты регистрации для проверки обоих сценариев (с компанией и без)
2. При необходимости добавить возможность привязки компании к пользователю позже через отдельный API endpoint
3. Рассмотреть возможность добавления валидации на уровне бизнес-логики для определенных типов пользователей
