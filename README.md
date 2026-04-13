# One Market

Laravel-проект запускается через один Docker-стек:

- базовый [docker-compose.yml](docker-compose.yml) для production-ориентированного запуска;
- локальный [docker-compose.override.yml](docker-compose.override.yml) для bind mount и Vite dev server;
- короткие команды через [Makefile](Makefile).

## Что нужно установить

Минимум:

- Docker Engine
- Docker Compose plugin
- GNU Make
- Git

Опционально:

- локальный PHP, если удобно генерировать `APP_KEY` или запускать локальные artisan/composer-команды вне Docker

## Структура Docker-файлов

- [Dockerfile](Dockerfile) — сборка `app`
- [docker-compose.yml](docker-compose.yml) — базовый стек
- [docker-compose.override.yml](docker-compose.override.yml) — локальные отличия
- [docker/app/php.ini](docker/app/php.ini) — базовые PHP настройки
- [docker/app/php.local.ini](docker/app/php.local.ini) — локальные override для PHP
- [docker/app/entrypoint.sh](docker/app/entrypoint.sh) — подготовка приложения, ожидание БД, миграции, optimize
- [docker/app/healthcheck-http.sh](docker/app/healthcheck-http.sh) — HTTP healthcheck `app`

## Что поднимается

Всегда:

- `app` — Laravel на `FrankenPHP`
- `queue` — Laravel queue worker
- `scheduler` — Laravel scheduler loop
- `db` — `mariadb:11.8`

Только локально:

- `vite` — dev server для `JS` и `CSS`

## Основные переменные

Главные переменные уже есть в [.env.example](.env.example):

- `APP_KEY` — обязателен
- `DOCKER_PROJECT_NAME` — имя Docker Compose проекта, по умолчанию `one-market`
- `DOCKER_UID`, `DOCKER_GID` — UID/GID пользователя хоста для локального `app`, `queue`, `scheduler`
- `APP_URL` — URL приложения
- `APP_PORT` — внешний HTTP порт
- `FORWARD_DB_PORT` — внешний порт MariaDB для локалки
- `VITE_PORT` — внешний порт Vite
- `VITE_HMR_HOST` — host для HMR в браузере
- `VITE_USE_POLLING` — polling для watch, если обычное отслеживание файлов не работает
- `APP_ENV` — `local` или `production`
- `APP_DEBUG` — в production должен быть `false`
- `APP_OPTIMIZE` — в production обычно `1`, локально обычно `0`
- `RUN_MIGRATIONS` — запускать ли миграции при старте `app`
- `WAIT_FOR_DATABASE` — ждать ли БД перед стартом
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` — настройки БД

По умолчанию `Makefile` работает с `.env`. Если нужен другой env-файл:

```bash
make ENV_FILE=.env.production build
```

`Makefile` берёт Docker Compose project name из `DOCKER_PROJECT_NAME`.
Из одного checkout запускай либо локальный стек, либо production стек.
Если переключаешься между режимами, сначала останови предыдущий:

```bash
make down
```

## Локальный запуск

### 1. Подготовить `.env`

```bash
cp .env.example .env
```

Заполни `APP_KEY`.

Если локальный PHP установлен:

```bash
php artisan key:generate
```

Если локального PHP нет:

```bash
make key-show
```

Эта команда выведет готовое значение `APP_KEY`. Его нужно вставить в `.env`.

### 2. Поднять стек

```bash
make build
```

После старта будут доступны:

- приложение: `http://localhost:8000`
- MariaDB: `127.0.0.1:3307`
- Vite dev server: `http://localhost:5173`

### 3. Проверить состояние

```bash
make ps
```

Контейнер `app` должен быть в статусе `healthy`.

## Как работать локально

Локальная схема настроена для обычной разработки без пересборки после каждой правки:

- `PHP` и `Blade` подхватываются через bind mount
- `JS` и `CSS` подхватываются через `vite`
- локальная очередь работает через `queue:listen`, поэтому не требует `queue:restart` после каждой правки кода
- `artisan make:*`, запущенный внутри `app`, создаёт файлы с UID/GID хоста, а не `root`
- локальный `app` собирается с Composer и dev dependencies, поэтому `composer`, `boost:mcp`, Pint и Pest доступны прямо в `app`
- локальный `app`, `queue` и `scheduler` используют общий `vendor` volume, поэтому зависимости не расходятся между контейнерами

Если на машине UID/GID не `1000:1000`, поменяй `DOCKER_UID` и `DOCKER_GID` в `.env`:

```bash
id -u
id -g
```

Обычный цикл такой:

```bash
make build
```

Дальше просто меняй код и при необходимости смотри логи:

```bash
make logs
```

## Когда локально нужен `make build`

`make build` нужен, если поменялось что-то инфраструктурное:

- [Dockerfile](Dockerfile)
- [docker-compose.yml](docker-compose.yml)
- [docker-compose.override.yml](docker-compose.override.yml)
- файлы в [docker/app](docker/app)

Если менялся `composer.json`, `composer.lock` или `package.json`, пересборка image обычно не нужна.

Если менялся `.env`, достаточно заново применить compose-конфигурацию:

```bash
make up
```

## Полезные локальные команды

Запуск:

```bash
make build
```

Запуск без пересборки:

```bash
make up
```

Остановка:

```bash
make down
```

Остановка с удалением volumes, включая БД:

```bash
make down-volumes
```

Логи:

```bash
make logs
```

Статус:

```bash
make ps
```

Экспорт БД в `docker/db/dump.sql.gz`:

```bash
make dump
```

Импорт БД из `docker/db/dump.sql.gz`:

```bash
make import
```

Тесты:

```bash
make test
```

## Production

Production использует только базовый compose-файл, без local override:

```bash
make build
```

В production режиме:

- `app` обслуживает HTTP сам через `FrankenPHP`
- `app` ждёт БД и проходит HTTP healthcheck через Laravel endpoint `/up`
- image собирается с `composer install --no-dev`
- при `APP_OPTIMIZE=1` выполняется `php artisan optimize`
- MariaDB наружу не публикуется

### Рекомендуемые production значения

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_OPTIMIZE=1
RUN_MIGRATIONS=0
WAIT_FOR_DATABASE=1
APP_URL=https://your-domain.com
APP_PORT=80
DB_HOST=db
DB_PORT=3306
DB_DATABASE=one_market
DB_USERNAME=laravel
DB_PASSWORD=strong-password
DB_ROOT_PASSWORD=strong-root-password
```

### Production deploy workflow

Если деплой делается прямо на сервере через `git pull`, рабочий сценарий такой:

```bash
make deploy
```

Почему именно так:

- `git pull` обновляет исходники на сервере
- `make build` собирает новые immutable images с новым кодом
- `php artisan migrate --force` применяет миграции
- `php artisan queue:restart` перезапускает долгоживущие queue workers на новой версии кода

Просто `git pull` без пересборки для этой схемы недостаточен, потому что код находится внутри image.

## Полезные production команды

Запуск:

```bash
make build
```

Запуск без пересборки:

```bash
make up
```

Остановка:

```bash
make down
```

Остановка с удалением volumes, включая БД:

```bash
make down-volumes
```

Логи:

```bash
make logs
```

Статус:

```bash
make ps
```

Экспорт БД в `docker/db/dump.sql.gz`:

```bash
make dump
```

Импорт БД из `docker/db/dump.sql.gz`:

```bash
make import
```

## Ограничения текущей схемы

- Production PHP image собирается через `composer install --no-dev`, поэтому dev-only Composer пакеты в production-контейнеры не попадают.

## Примечания

- `app` healthcheck использует встроенный Laravel endpoint `/up`.
- Laravel рекомендует держать `APP_DEBUG=false` в production.
- После релиза queue workers нужно перезапускать через `php artisan queue:restart`, потому что это долгоживущие процессы.
- Все доступные команды можно посмотреть через:

```bash
make help
```
