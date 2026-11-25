# Деплой и боевой сервер

Production — Docker Compose стек `one-market` из `docker-compose.yml` без `docker-compose.override.yml`
на сервере `esepteu` (общем с esepteu): приложение (`app`, Octane на FrankenPHP), очередь (`queue`),
планировщик (`scheduler`), MariaDB (`db`) и Redis (`redis`). Makefile выбирает этот стек сам, по
`APP_ENV=production` в `.env` сервера. Код лежит внутри образа, поэтому `git pull` без пересборки
ничего не меняет: новый код выкладывает `make deploy`.

## Доступ к серверу

Общая часть — команды `server-kit`, `server-status`, `harden` и общее правило «Боевые серверы» для
агентов — в репозитории [server-kit](https://github.com/Yeleup/server-kit) (приватный: команды и что
они делают — README, подключение сервера — `docs/new-server.md`, переезд — `docs/migration.md`).
Здесь — то, что относится к one-market.

- **Кто заходит.** Владелец. Агенты (Claude, Codex) — по общему правилу «Боевые серверы», которое им
  ставит `server-kit install`; координаторы очереди, исполнители, ревьюеры и задачи по расписанию на
  сервер не ходят. Правило проекта — [.ai/guidelines/prod-server.md](../.ai/guidelines/prod-server.md)
  (оно же в `CLAUDE.md`, `AGENTS.md` и `GEMINI.md`).
- **Как.** Сервер подключён к server-kit под именем `esepteu`: алиас `esepteu-prod`, пользователь
  `developer`, проект в `~/one-market` (клон `git@github.com:Yeleup/one-market.git`). Адрес сервера
  есть только в `~/.ssh/config` владельца и в настройках server-kit, в репозиторий его не пишут.
  Технического замка для агентов нет — держит правило.
- **Деплой агентом.** `server-kit job esepteu one-market-deploy -- 'cd ~/one-market && make deploy'`,
  затем `server-kit job-wait esepteu <задание>` (имя задания печатает `job`). Первый деплой с
  блокировкой — иначе, [см. ниже](#первый-деплой-с-блокировкой). Об успехе агент говорит только после
  кода завершения 0 и проверки, что приложение отвечает. Отката нет: команды отката не существует.
  Состояние — `ssh esepteu-prod 'cd ~/one-market && make deploy-status'`, нагрузка —
  `server-status esepteu` (раздел `[one-market]`). Владелец может деплоить и сам:
  `cd ~/one-market && make deploy`. Если в это время уже идёт деплой агента (или наоборот), вторая
  команда сразу отказывает ([«Одна команда за раз»](#одна-команда-за-раз)).
- **Лог приложения.** Каталог `storage` проекта смонтирован в контейнеры, и Laravel пишет лог в
  `~/one-market/storage/logs/` (`laravel.log` или ежедневные `laravel-<дата>.log` — по `LOG_CHANNEL` и
  `LOG_STACK` в `.env`). `server-kit logs esepteu` его не читает: настройка лога в server-kit
  (`app_log`) одна на сервер, и на `esepteu` она указывает на лог esepteu.
- **Соседи по серверу.** Сервер общий: рядом работает esepteu (compose-проект `eseply`, приложение на
  `127.0.0.1:8600`), там же лежат остановленные копии sala и satu-plus. Цели Makefile one-market меняют
  только свой compose-проект — `one-market` (`DOCKER_PROJECT_NAME` в `.env` сервера, контейнеры
  `one-market-app-1`, `one-market-queue-1` и т. д.). Перезапуск Docker и перезагрузка сервера задевают
  и esepteu.
- **Что открыто наружу.** Порты сервера и файрвол описаны в `docs/deployment.md` esepteu (раздел
  «Доступ к серверу»), текущее состояние показывает `harden esepteu --check`. `docker-compose.yml`
  публикует только порт приложения (`${APP_PORT:-8000}:8000`, на сервере — 8500) без адреса, то есть на
  всех адресах сервера: по IPv4 Docker публикует его мимо ufw, и приложение открывается и напрямую, без
  веб-сервера. Закрыть его можно, привязав к localhost в `.env` сервера (`APP_PORT=127.0.0.1:8500`),
  если веб-сервер ходит на `127.0.0.1:8500`. MariaDB one-market наружу не публикуется (порт базы есть
  только в `docker-compose.override.yml` локальной разработки).
- **Если ноутбук или его копия попали в чужие руки** — порядок в server-kit, `docs/new-server.md`,
  одноимённый раздел.

## Обычный деплой

```bash
cd ~/one-market && make deploy
```

Если на сервере ещё Makefile без блокировки (`make deploy-status` отвечает
`No rule to make target 'deploy-status'`), первый раз деплоят иначе — по
[«Первый деплой с блокировкой»](#первый-деплой-с-блокировкой).

Порядок:

1. Проверки: команда запущена не из git worktree, в `.env` есть `DOCKER_PROJECT_NAME`, compose-проект
   принадлежит этому каталогу.
2. Блокировка production-стека ([«Одна команда за раз»](#одна-команда-за-раз)) — до `git pull`.
3. `git pull`. Если он не удался, деплой останавливается: ничего не пересобрано.
4. Дальше шаги идут по скачанной версии Makefile: make прочитал Makefile до `git pull`, поэтому
   шаги выполняет новый make, который читает скачанный. Изменение самих шагов действует уже в том
   деплое, который его принёс.
5. `docker compose up -d --build --remove-orphans`: сборка образа, затем пересоздание контейнеров, у
   которых изменились образ или описание, — `app`, `queue`, `scheduler`. Пока пересоздаётся `app`,
   приложение недоступно: деплой идёт с коротким простоем. `db` и `redis` пересоздаются, только если
   изменилось их описание в `docker-compose.yml`. Если сборка упала, контейнеры остаются прежними.
6. `php artisan migrate --force` в контейнере `app`. Миграции идут после запуска нового кода: несколько
   секунд новый код работает со старой схемой базы. Ошибка миграции останавливает деплой — новый код
   уже запущен, `queue:restart` не выполнен.
7. `php artisan queue:restart`: воркеры очереди заканчивают текущее задание и перезапускаются.
8. Блокировка освобождается: после последнего шага, после ошибки любого шага и при обрыве команды.

Если деплой оборвался (Ctrl+C, разрыв связи, ошибка шага), блокировка свободна, как только закончились
процессы команды. Повторный `make deploy` продолжает с того, что осталось: `git pull` не скачает уже
скачанное, `docker compose up` приводит контейнеры к описанию, `migrate` применяет оставшиеся
миграции.

Контейнеры получают весь `.env` сервера (`env_file` в `docker-compose.yml`): новая переменная доходит
до приложения, очереди и планировщика после следующего `make deploy` (или `make up`), без правки
`docker-compose.yml`.

### Первый деплой с блокировкой

Один раз — на сервере, где Makefile ещё старый, без блокировки. Этот деплой меняет не только Makefile:

- **`DOCKER_PROJECT_NAME` обязателен.** Старый Makefile без него брал имя `one-market`, новый
  отказывает, а `docker-compose.yml` без него не читается. В `.env` сервера должна быть строка
  `DOCKER_PROJECT_NAME=one-market` — с другим именем Docker создал бы новый пустой стек со своей базой
  рядом с работающим.
- **Контейнеры начинают получать весь `.env` сервера.** Раньше до них доходили только переменные из
  блока `x-app-environment` в `docker-compose.yml` (`APP_*`, `DB_*`, `REDIS_*`, `CACHE_STORE`,
  `QUEUE_CONNECTION`, `SESSION_DRIVER`), остальные настройки приложение брало по умолчанию из
  `config/`. Теперь действуют и все прочие строки `.env` сервера — почта, логи (`LOG_*`), часовой пояс
  показа, реквизиты поставщика и т. д. Перед деплоем владелец сверяет `.env` сервера: в нём не должно
  остаться строк, которые сейчас не действуют и не должны начать действовать.

Обычный `make deploy` в первый раз не годится: make читает Makefile один раз, до `git pull`, и весь
этот деплой — `git pull` и старые шаги — идёт по старому Makefile без блокировки. Поэтому:

1. Владелец проверил `.env` сервера (два пункта выше).
2. Убедиться, что на сервере не идёт другая команда стека one-market — деплой агента, `make up` и т. п.:
   команды старого Makefile блокировку не берут и не проверяют, исключить их можно только так.
   `pgrep -ax 'make|docker|docker-compose'` ничего не показывает (сравнивает имя процесса, поэтому
   оболочка, в команде которой есть слово make, не в счёт). Команды esepteu на том же сервере берут
   свою блокировку и стек one-market не трогают, но их процессы `pgrep` тоже покажет — тогда дождаться
   их конца.
3. Одной командой:
   ```bash
   cd ~/one-market && git pull && make deploy
   ```
   Агент — по просьбе владельца:
   `server-kit job esepteu one-market-deploy -- 'cd ~/one-market && git pull && make deploy'`, затем
   `job-wait`. Первый `git pull` кладёт новый Makefile и `docker/deploy/deploy.sh` и стек не меняет: код
   попадает в контейнеры только при сборке, а из каталога проекта в них смонтирован лишь `storage`.
   `make deploy` запускается после него и читает уже новый Makefile, поэтому блокировка — с первого
   шага, а его собственному `git pull` скачивать уже нечего. Если в `.env` нет `DOCKER_PROJECT_NAME`,
   `make deploy` сразу отказывает и ничего не делает: строку добавляют и повторяют `make deploy`.
4. `make deploy-status` отвечает и показывает `lock: free, last taken by make deploy …`; приложение
   отвечает на порту 8500.

Дальше — обычный `make deploy`.

### Команды Makefile в production

| Команда | Что делает | Блокировка |
|---|---|---|
| `make deploy` | деплой, порядок выше | берёт до `git pull`, держит до последнего шага |
| `make deploy-status` | коммит, отставание от ветки, которую приносит `git pull`, правки на сервере, блокировка, контейнеры | не берёт, работает и во время деплоя |
| `make build` | собирает образ и пересоздаёт изменившиеся контейнеры, как шаг 5 деплоя, но без `git pull`, миграций и `queue:restart` | берёт |
| `make up` | запускает стек без сборки; пересоздаёт контейнеры, у которых изменилось описание | берёт |
| `make down` | останавливает и удаляет контейнеры; тома с базой и Redis остаются | берёт |
| `make down-volumes` | то же и удаляет тома — базу и данные Redis | берёт |
| `make restart` | `make down`, затем `make up` | берёт одну на оба шага: между ними другая команда не начнётся |
| `make ps`, `make logs`, `make artisan`, `make shell`, `make octane-status`, `make octane-reload`, `make dump`, `make dump-media` | смотрят на стек или работают в запущенных `app` и `db` | не берёт |
| `make import-media` | заменяет файлы `storage/app` содержимым архива внутри запущенного `app`; в production не отказывает — только по просьбе владельца, как при переезде | не берёт |
| `make import`, `make db-up`, `make test` | в production отказывают | — |

### Одна команда за раз

`make deploy`, `make build`, `make up`, `make down`, `make down-volumes` и `make restart` меняют
production-стек и выполняются по одной: каждая берёт общую блокировку и держит её до конца,
`make deploy` — ещё до `git pull`. Так деплой агента через `server-kit job` и команда владельца из
своего терминала не перемешаются. Если блокировка занята, вторая команда сразу завершается с ошибкой
и ничего не делает — не ждёт и в очередь не встаёт. В ошибке — что уже идёт, его процесс и с какого
времени:

```
error: the production stack is busy: make deploy (pid 12345) since 2026-11-25 14:05:10 +0500. Nothing was done; run the command again when that one has finished
```

Команду повторяют, когда та закончится. `make deploy-status` и остальные цели блокировку не берут и
работают и во время деплоя. `make deploy-status` показывает, занята ли она и кем:

```
commit: 1a2b3c4 2026-11-25 12:00 +0500 <тема коммита>
upstream: origin/master, commits behind: 2, ahead: 0 (fetched just now)
local changes: none
lock: held by make deploy (pid 12345) since 2026-11-25 14:05:10 +0500
containers:
<docker compose ps -a>
```

Ветку, которую приносит `git pull` (`origin/master`), `make deploy-status` скачивает с GitHub в
отдельную ссылку `refs/deploy-status/upstream`: ни `origin/master`, ни `FETCH_HEAD`, ни рабочая копия
от этого не меняются, и `git pull` идущего деплоя не споткнётся о них. Если GitHub отказал или не
ответил за 20 секунд, отставание считается по последнему `git pull`, а в строке сказано почему.
`local changes` — изменённые на сервере файлы из git: с ними `git pull` может отказаться.

Блокировку держит не файл, а процессы команды: сама команда и всё, что она запустила (`git pull`,
`make`, `docker compose`). Ни один из них не меняет стек без блокировки, и она свободна, когда
закончились все. Ctrl+C, закрытый терминал и перезагрузка сервера останавливают команду целиком —
блокировка сразу свободна, снимать её руками не нужно. Если остановить только сам процесс команды
(`kill -9 <pid>`), запущенные им `git pull` или `docker compose` доработают и до своего конца держат
блокировку.

В `.deploy/lock` — строка о команде, которая взяла блокировку последней; сама она ничего не
блокирует. Отказ и `make deploy-status` показывают её, только пока процесс этой команды держит
блокировку. Иначе они перечисляют процессы, которые держат блокировку сейчас:

```
error: the production stack is busy: the lock is held by pid 23456 (git pull); .deploy/lock names make deploy (pid 12345) since 2026-11-25 14:05:10 +0500, which no longer holds it: …
```

Если такой процесс завис, его останавливают по pid из ошибки (`kill <pid>`; агент — только по
просьбе владельца). Когда закончились все перечисленные, блокировка свободна.

Что держать в голове:

- Процессы видны только своему пользователю, поэтому `make` на сервере запускают от `developer` и не
  через `sudo`. Если `.deploy/lock` создала команда от root, команды `developer` отказывают с
  «cannot create .deploy/lock», пока файл не вернут `developer`.
- `.deploy/` нет в git (`.gitignore`) и в образе (`.dockerignore`), `git pull` его не трогает. Пока
  идёт команда, `.deploy/` не удаляют и `git clean -x` на сервере не запускают: новый файл — новая
  блокировка, и вторая команда её получила бы.
- Блокировка у one-market своя (`~/one-market/.deploy/lock`): деплой esepteu на том же сервере её не
  берёт и с деплоем one-market может идти одновременно.
- На сервере нужен `flock` (util-linux).

Локально (`APP_ENV` не `production`) и в git worktree блокировки нет: `make build`, `make up`,
`make down`, `make down-volumes` и `make restart` работают без неё. `make deploy` берёт её всегда:
он всегда работает с production-стеком.

## Перенос на другой сервер

Общий порядок — временный ключ server-kit, перенос файлов и порядок переключения — в
`docs/migration.md` [server-kit](https://github.com/Yeleup/server-kit), подключение нового сервера —
в его `docs/new-server.md`. Здесь — шаги one-market. Команды `server-kit` — с ноутбука владельца;
`<новый>` — имя нового сервера в server-kit, как `esepteu` у нынешнего.

Команды стека на серверах, кроме целей Makefile, выполняются в одной оболочке после двух строк:
`.env` загружен, compose-файл — тот же, что у Makefile в production (без `-f docker-compose.yml`
compose подключил бы `docker-compose.override.yml` локальной разработки):

```bash
cd ~/one-market && set -a && . ./.env && set +a
prod() { docker compose -p "$DOCKER_PROJECT_NAME" --env-file .env -f docker-compose.yml "$@"; }
```

1. **Новый сервер готов, стек не запущен.** Сервер подключён к server-kit, проект склонирован в
   `~/one-market` под `developer` ключом id_rsa владельца, без новых ключей (README server-kit,
   «Решения владельца»): `git clone git@github.com:Yeleup/one-market.git ~/one-market`. Веб-сервер
   перед приложением настроен так же, как на старом; временный ключ открыт:
   `server-kit transfer esepteu <новый> open --path /home/developer/one-market`. Стек на новом сервере
   не запускают до шага 7: воркеры очереди и планировщик не должны работать на двух серверах сразу.
2. **`.env` — целиком и тот же:** `server-kit transfer esepteu <новый> pull .env`. С другим `APP_KEY`
   перестанут читаться зашифрованные cookie и сессии: все пользователи выйдут из панели. Значения из
   `.env` в чат не выводят.
3. **Старый сервер: сайт закрыт, планировщик остановлен, очередь разобрана.** Сайт закрывают на
   веб-сервере (как и почему — общий порядок server-kit). Затем:
   ```bash
   prod stop scheduler
   prod exec -T queue php artisan queue:monitor "${QUEUE_CONNECTION:-redis}:default"
   ```
   `queue:monitor` повторяют, пока очередь `default` не опустеет, затем `prod stop -t 90 queue`:
   воркер, который успел взять задание, дорабатывает его.
4. **Старый сервер: финальные дампы, затем приложение останавливают.** `make dump-media` собирает архив
   внутри работающего `app`, поэтому `app` останавливают после него:
   ```bash
   make dump         # база → docker/db/dump.sql.gz
   make dump-media   # storage/app → docker/db/storage.tar.gz
   prod stop app
   ```
   Логи (`storage/logs`) не переносятся. Что лежит в Redis, задаёт `.env` (`CACHE_STORE`,
   `SESSION_DRIVER`, `QUEUE_CONNECTION`; без них compose подставляет Redis): кэш соберётся заново,
   сессии — это входы в панель, при очереди на Redis в нём же отложенные задания. Нужен ли снимок
   Redis, решают при переезде (общий порядок — server-kit).
5. **На новый сервер:**
   `server-kit transfer esepteu <новый> pull docker/db/dump.sql.gz docker/db/storage.tar.gz`, затем
   `server-kit job-wait <новый> <задание>`.
6. **Новый сервер: только база и Redis, восстановление.** `make import` в production отказывает, поэтому
   база заливается в пустую базу `DB_DATABASE`, которую MariaDB создала при первом запуске тома, а файлы
   распаковываются в каталог проекта (пути архива — `storage/app/…`):
   ```bash
   prod up -d --wait db redis
   gunzip -c docker/db/dump.sql.gz | sed -E 's/DEFINER=`[^`]*`@`[^`]*`//g' \
       | prod exec -T db sh -c 'MYSQL_PWD="$MARIADB_PASSWORD" exec mariadb -u"$MARIADB_USER" "$MARIADB_DATABASE"'
   tar -xzf docker/db/storage.tar.gz
   ```
7. **Новый сервер: первый и единственный запуск всего стека** — `make deploy` (агент — через
   `server-kit job <новый> first-deploy -- 'cd ~/one-market && make deploy'` и `job-wait`). Воркеры и
   планировщик стартуют только здесь. Проверяют, что приложение отвечает.
8. **Переключение DNS.** TTL понижают заранее, по общему порядку server-kit. Когда домен уже ведёт на
   новый сервер, на нём выпускают сертификаты.
9. **После переезда:** `server-kit transfer esepteu <новый> revoke`; `docker/db/dump.sql.gz` и
   `docker/db/storage.tar.gz` на обоих серверах удаляют — в них данные клиентов и заказов. Каталог
   `.deploy/` не переносится: на новом сервере файл блокировки создаёт первая команда. Если esepteu
   переезжает на тот же новый сервер, имя и алиас переводит владелец (`server-kit rename`); если
   one-market переезжает один, правило проекта и этот документ получают имя нового сервера.
