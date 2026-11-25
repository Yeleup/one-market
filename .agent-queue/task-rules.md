# Правила проекта для заданий очереди (one-market)

<!-- Координатор вставляет этот файл целиком в задания исполнителю и ревьюеру. Меняет файл только владелец. -->

Читать перед задачей: issue целиком. Отдельных документов ТЗ и бизнес-правил в репозитории нет: поведение — в коде и
тестах (tests/Feature, tests/Unit).

- Тесты: только `make test` (одноразовый контейнер с кодом этого worktree и его тестовой базой MariaDB); при работе
  `test_args="--compact --filter=…"`; перед отчётом — полный `make test` на закоммиченном дереве без незакоммиченных и
  неотслеживаемых файлов, в отчёте — сводка Pest. Никогда хостовый `php artisan`, `composer`, `vendor/bin/pest` и
  `php artisan test` через `docker exec` (он идёт по базе разработки основного чекаута, RefreshDatabase её стирает).
- PHP: после правок `vendor/bin/pint --dirty --format agent` на хосте (у worktree свой vendor/).
- Laravel 13, Filament 5 (админка), Inertia 3 + Svelte (витрина, resources/js/Pages), Tailwind 4: API — установленных
  версий (`search-docs` проектного Boost), не по памяти. Новые зависимости — только если их называет issue.
- Интерфейс: тексты — на всех языках проекта (lang/ru, lang/kk, lang/en и переводы витрины); проверка на ширине
  компьютера и телефона.
- Даты и время хранятся в UTC, людям показываются в часовом поясе APP_DISPLAY_TIMEZONE (по умолчанию Asia/Almaty).
- Документация: файлов документации, кроме README.md и docs/deployment.md, в репозитории нет; создавать их — только
  если просит issue.
- Octane: без состояния запроса в синглтонах и static-свойствах; в конструктор синглтона не передавать контейнер,
  запрос и конфиг.
- Docker, Makefile, orca.yaml, docker/app/*, docker/worktree/*: ту же правку в обобщённом виде — в
  laravel-docker-template (.ai/guidelines/laravel-docker-template.md).
- Сообщения коммитов — по-русски, «Раздел: что изменилось для пользователей и почему (#N)», без префикса
  `type(scope):`.
- Worktree готовит `make worktree-setup` (orca.yaml setup): свой .env, порты, базы и пользователь на MariaDB основного
  чекаута, свой vendor/; убирает их архивный хук orca.yaml при удалении worktree. Make пишет, что .env не для этого
  worktree или нет vendor/, — `make worktree-setup`, и ничего больше. Новые миграции — `make artisan
  artisan_args="migrate"` (база worktree).
- Проверка интерфейса — только `make up` в своём worktree (изолированный стенд, база пустая:
  `make artisan artisan_args="db:seed"`), после — `make down`; никогда стенд основного чекаута (его APP_URL, порт Vite,
  контейнеры).
- laravel-boost — только проектный MCP-сервер (отвечает по этому worktree и его базе); пользовательский плагин Boost
  отвечает из основного чекаута.
- В worktree нельзя: `docker exec one-market-app-1` и другие контейнеры основного чекаута, `make init`, копировать или
  связывать .env основного чекаута, `DOCKER_PROJECT_NAME` или `docker compose -p` основного проекта, `make deploy`,
  заходить на боевой сервер.
- Ловушки: `make test` форсирует `QUEUE_CONNECTION=sync`, `CACHE_STORE=array` и `SESSION_DRIVER=array` (джобы в тестах
  синхронны). Filament: `assertMountedActionModalSee` зелёный и без `requiresConfirmation` — проверять
  `isConfirmationRequired()`.
