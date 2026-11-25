# One Market

Laravel-проект запускается через один Docker-стек:

- базовый [docker-compose.yml](docker-compose.yml) для production-ориентированного запуска;
- локальный [docker-compose.override.yml](docker-compose.override.yml) для bind mount и Vite dev server;
- короткие команды через [Makefile](Makefile).

Docker-настройка взята из общего шаблона [laravel-docker-template](laravel-docker-template) (тот же, что в
esepteu, sala, barlau): правка Docker, Makefile или `orca.yaml` в корне повторяется там в обобщённом виде
([.ai/guidelines/laravel-docker-template.md](.ai/guidelines/laravel-docker-template.md)).

## Что нужно установить

- Docker Engine
- Docker Compose plugin
- GNU Make
- Git

Для git worktree ещё `flock`, `ss`, `realpath`, `cp --reflink` (Linux). Локальный PHP не нужен: все команды
PHP, Composer и Node идут в контейнерах.

## Структура Docker-файлов

- [Dockerfile](Dockerfile) — сборка `app`
- [docker-compose.yml](docker-compose.yml) — базовый стек
- [docker-compose.override.yml](docker-compose.override.yml) — локальные отличия
- [.env.docker.example](.env.docker.example) — переменные Docker, которые `make init` добавляет в `.env`
- [docker/app/php.ini](docker/app/php.ini) — базовые PHP настройки
- [docker/app/php.local.ini](docker/app/php.local.ini) — локальные override для PHP
- [docker/app/entrypoint.sh](docker/app/entrypoint.sh) — подготовка приложения, ожидание БД, миграции, optimize
- [docker/app/healthcheck-http.sh](docker/app/healthcheck-http.sh) — HTTP healthcheck `app`
- [docker/worktree/worktree.sh](docker/worktree/worktree.sh) и [orca.yaml](orca.yaml) — отдельное окружение для каждого git worktree
- [docker/deploy/deploy.sh](docker/deploy/deploy.sh) — блокировка production-стека для `make deploy` и других команд
- [docker/test-result.sh](docker/test-result.sh) — запись итога `make test` для очереди агентов

## Что поднимается

Всегда:

- `app` — Laravel на `FrankenPHP` (Octane)
- `queue` — Laravel queue worker
- `scheduler` — Laravel scheduler loop
- `db` — `mariadb:11.8`
- `redis` — Redis

Только локально:

- `vite` — dev server для `JS` и `CSS`

## Основные переменные

Главные переменные — в [.env.example](.env.example) и [.env.docker.example](.env.docker.example):

- `APP_KEY` — обязателен (`make init` создаёт его сам)
- `DOCKER_PROJECT_NAME` — имя Docker Compose проекта, обязателен: у основного чекаута `one-market`, у каждого git worktree — своё
- `DOCKER_UID`, `DOCKER_GID` — UID/GID пользователя хоста для локального `app`, `queue`, `scheduler`
- `APP_URL`, `APP_PORT` — URL и внешний HTTP порт приложения
- `FORWARD_DB_PORT` — внешний порт MariaDB (только локально)
- `VITE_PORT`, `VITE_HMR_HOST`, `VITE_USE_POLLING` — Vite
- `APP_ENV` — `local` или `production`; Makefile выбирает по нему стек
- `APP_DEBUG` — в production должен быть `false`
- `APP_OPTIMIZE` — в production обычно `1`, локально обычно `0`
- `RUN_MIGRATIONS` — запускать ли миграции при старте `app`
- `WAIT_FOR_DATABASE` — ждать ли БД перед стартом
- `DB_*`, `DB_ROOT_PASSWORD` — настройки БД, `MARIADB_TEST_DATABASE` — тестовая база

Контейнеры `app`, `queue` и `scheduler` получают весь `.env` (`env_file`): новая переменная доходит до них после
`make up`, без правки `docker-compose.yml`.

По умолчанию `Makefile` работает с `.env`. Если нужен другой env-файл:

```bash
make ENV_FILE=.env.production build
```

## Локальный запуск

```bash
make init
make build
```

`make init` создаёт `.env` из `.env.example`, если его нет, добавляет недостающие переменные из
`.env.docker.example` (существующие значения не трогает), создаёт `APP_KEY` и ставит зависимости Composer и Node.

После старта будут доступны (порты — из `.env`):

- приложение: `http://localhost:8000`
- MariaDB: `127.0.0.1:3307`
- Vite dev server: `http://localhost:5173`

Состояние — `make ps`: контейнер `app` должен быть в статусе `healthy`.

## Как работать локально

- `PHP` и `Blade` подхватываются через bind mount
- `JS` и `CSS` подхватываются через `vite`
- локальная очередь работает через `queue:listen`, поэтому не требует `queue:restart` после каждой правки кода
- `make artisan`, `make composer`, `make shell` и `make test` запускают одноразовые контейнеры, которые монтируют
  текущий чекаут; файлы создаются с UID/GID хоста
- если на машине UID/GID не `1000:1000`, поменяй `DOCKER_UID` и `DOCKER_GID` в `.env` (`id -u`, `id -g`)

`make build` нужен, если поменялось что-то инфраструктурное: [Dockerfile](Dockerfile), compose-файлы, файлы в
[docker/app](docker/app). Если менялся `.env`, достаточно `make up`.

## Полезные команды

```bash
make up                                  # запуск без пересборки
make down                                # остановка
make down-volumes                        # остановка с удалением томов, включая БД
make db-up                               # только db и redis (достаточно для make test)
make logs                                # логи
make ps                                  # статус
make shell                               # shell в одноразовом контейнере app
make artisan artisan_args="route:list"   # artisan
make composer composer_args="install"    # composer
make npm npm_args="run build"            # npm в контейнере vite
make dump                                # БД → docker/db/dump.sql.gz
make import                              # БД ← docker/db/dump.sql.gz (пересоздаёт базу; в production отказывает)
make dump-media                          # storage/app → docker/db/storage.tar.gz (через контейнер app)
make import-media                        # storage/app ← docker/db/storage.tar.gz (через контейнер app)
make octane-status                       # состояние Octane
make octane-reload                       # мягкий перезапуск воркеров Octane
make help                                # все команды
```

## Тесты

```bash
make test
make test test_args="--compact --filter=SomeTest"
```

`make test` запускает тесты текущего чекаута (основного или git worktree) в одноразовом контейнере на его
тестовой базе MariaDB (`MARIADB_TEST_DATABASE`); нужна только запущенная база (`make up` или `make db-up`).
Никогда не запускай `php artisan test` на хосте или через `docker exec`: так тесты идут по базе разработки, и
`RefreshDatabase` её стирает.

Каждый прогон `make test` записывает итог для очереди агентов в
`~/.cache/agent-queue-kit/one-market/test-results/<SHA>/` ([docker/test-result.sh](docker/test-result.sh)): очередь
вливает коммит только с полным зелёным прогоном. Вывод и код выхода `make test` от этого не меняются.

## Git worktrees и очередь агентов

Изменения кода идут через очередь агентов ([agent-queue-kit](https://github.com/Yeleup/agent-queue-kit)): задача —
issue GitHub с меткой `agent`, Claude реализует её в git worktree, Codex ревьюит, PR вливается после чистого ревью.
Настройки очереди — [.agent-queue/](.agent-queue), правило входа для агентов —
[.ai/guidelines/agent-queue.md](.ai/guidelines/agent-queue.md).

Каждому git worktree нужен свой `.env`: копия основного заставила бы `make up` в worktree перехватить контейнеры
основного чекаута, а `make test` — тестировать его код. В новом worktree (Orca делает это сам через
[orca.yaml](orca.yaml)):

```bash
make worktree-setup
```

Он пишет `.env` worktree со своими `DOCKER_PROJECT_NAME`, портами, базами (`one_market_wt_<имя>_<id>` и `..._test`)
и пользователем базы на MariaDB основного чекаута, копирует `vendor/` и запускает миграции. Дальше в worktree
`make test`, `make artisan`, `make composer` и `make shell` работают на его базах, а `make up` поднимает
изолированный стенд с пустой базой. Перед удалением worktree — `make worktree-archive` (Orca запускает его сам при
удалении с хуками); остатки удалённых worktree показывает `make worktree-prune` в основном чекауте
(`CONFIRM=yes` — удалить).

В настройках репозитория в Orca: **Command source** — **orca.yaml only**, включить **Wait for setup to complete
before starting agent**, **Worktree Shared Paths** оставить пустым.

## Production

Боевой сервер, деплой, команды Makefile в production и переезд — [docs/deployment.md](docs/deployment.md).
Коротко: на сервере `.env` с `APP_ENV=production` и `DOCKER_PROJECT_NAME=one-market`, деплой —

```bash
make deploy
```

(`git pull`, сборка образа, `migrate --force`, `queue:restart`; код лежит внутри образа, поэтому `git pull` без
пересборки ничего не меняет). Команды, которые меняют production-стек, идут по одной под общей блокировкой;
`make deploy-status` — только чтение.

В production режиме:

- `app` обслуживает HTTP сам через `FrankenPHP` и проходит HTTP healthcheck через Laravel endpoint `/up`
- image собирается с `composer install --no-dev`
- при `APP_OPTIMIZE=1` выполняется `php artisan optimize`
- MariaDB наружу не публикуется
