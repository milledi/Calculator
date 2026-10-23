# Кредитный калькулятор

Калькулятор аннуитетных платежей с отправкой заявки и админкой для её обработки.

- **Фронтенд** — React 18 + TypeScript + Tailwind, архитектура Feature-Sliced Design, сборка Vite.
- **Бекенд** — PHP 8.3 без фреймворка (Apache + mod_php, PDO), PostgreSQL 16.
- Всё запускается через Docker Compose.

## Запуск

```bash
cp .env.example .env        # поменяйте пароли
docker compose up --build
```

| Что | Адрес |
|---|---|
| Калькулятор (Vite dev-сервер) | http://localhost:5173 |
| Админка (логин/пароль из `.env`, по умолчанию `admin`/`admin`) | http://localhost:5173/admin |
| API напрямую | http://localhost:4000/api/health |

Прод-вариант (фронт собран и отдаётся nginx, API наружу не публикуется):

```bash
docker compose -f docker-compose.prod.yml up --build -d   # http://localhost:8080
```

В проде `PG_PASSWORD` и `ADMIN_PASSWORD` обязательны — без них compose не стартует.

## Тесты

```bash
npm test                                                  # фронтенд, Jest
docker compose --profile test run --rm --build api-tests  # бекенд, PHPUnit
```

Расчёт и валидация на сервере проверяются на тех же контрольных значениях, что и на фронте:
платёж, посчитанный PHP, совпадает с браузерным до копейки.

## API

| Метод и путь | Что делает |
|---|---|
| `GET /api/health` | `{"ok": true}` — проверка живости |
| `POST /api/applications` | Создать заявку. Тело — JSON `{loanType, amount, termMonths, fullName, monthlyIncome, hasCitizenship, consentGiven}`. Сервер сам проверяет данные и пересчитывает платёж. `201` — созданная заявка, `400` — `{"error": "validation_error", "issues": [{"path", "message"}]}` |
| `GET /admin[?status=new]` | Админка: список заявок с фильтром по статусу (Basic-авторизация) |
| `POST /admin/{id}/status` | Смена статуса из админки (Basic-авторизация + CSRF-токен) |

Продукты и ставки: ипотека 18%, автокредит 23,5%, потребительский 22,2%, «деньги до зарплаты» 50%.
Платёж не должен превышать 50% дохода.

## Устройство бекенда

```
server/
  public/index.php        единая точка входа (Apache FallbackResource)
  src/
    Kernel.php            маршруты и сборка зависимостей
    Config.php            настройки из переменных окружения
    Database.php          PDO и миграции (таблица schema_migrations)
    Http/                 Request, Response, Router
    Loan/                 продукты (enum LoanType) и аннуитетный расчёт
    Application/          валидация заявки, статусы, репозиторий (PDO)
    Controller/           API заявок и админка
    Security/             Basic-авторизация, CSRF, сессия
    View/                 рендер шаблонов с экранированием
  templates/admin.php     страница админки
  migrations/             SQL-миграции, применяются при старте контейнера
  tests/                  PHPUnit
```

Без сторонних библиотек: Composer нужен только для автозагрузки классов и PHPUnit.
Схема БД та же, что была у прежней Node-версии, поэтому существующая база подхватывается без потерь.

## Безопасность

- Сервер не доверяет числам клиента: проверяет все поля и сам считает платёж.
- SQL — только подготовленные запросы.
- Весь вывод в админке экранируется; строгий Content-Security-Policy, запрет встраивания во фрейм.
- Пароль админки сравнивается за постоянное время (`hash_equals`).
- Формы админки защищены CSRF-токеном: браузер подставляет Basic-авторизацию сам, поэтому без токена чужой сайт мог бы менять статусы.
- PostgreSQL не публикует порт наружу.
