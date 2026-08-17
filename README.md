# 🌊 River Notetaker

> A personal study companion for organizing notes, concepts, and reflections by discipline — built with Laravel and Livewire.

[![Tests](https://github.com/davigfarias/river-notetaker/actions/workflows/tests.yml/badge.svg)](https://github.com/davigfarias/river-notetaker/actions/workflows/tests.yml)
![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire Version](https://img.shields.io/badge/Livewire-4-4E56A6?logo=livewire&logoColor=white)
![Flux UI](https://img.shields.io/badge/Flux%20UI-2-000000)
![Pest](https://img.shields.io/badge/Pest-5-8B5CF6)
![License](https://img.shields.io/badge/license-MIT-blue)

## About

**River Notetaker** is a Laravel + Livewire application for capturing structured study notes. Each note belongs to a **discipline** (subject) and can carry **tags**, a glossary of **concepts**, **pastoral advice**, **references** (books, articles, videos, films, music, series), personal **impressions**, and **life experiences**. A dedicated concepts page lets you browse the growing glossary alphabetically or search across everything with full-text search powered by Laravel Scout.

The UI is currently in Portuguese (pt-BR).

<!-- Add screenshots / GIFs here -->
<!--
![Dashboard](docs/screenshots/dashboard.png)
![Note editor](docs/screenshots/create-note.png)
-->

## Features

- 📚 **Disciplines** — organize notes into custom subjects, each with its own icon
- 📝 **Rich notes** — title, tags, impressions, life experiences, and a markdown editor (via EasyMDE)
- 🧠 **Concepts glossary** — attach term/definition pairs to a note; browse them alphabetically or search on a dedicated page
- 🙏 **Pastoral advice** — capture categorized advice tied to a note
- 🔖 **References** — link books, articles, videos, films, music, and series to a note, each with its own icon
- 🔍 **Full-text search** — powered by [Laravel Scout](https://laravel.com/docs/scout)
- ⚡ **Single-file Livewire components** — built with Livewire 4 and [Flux UI](https://fluxui.dev)
- ✅ **Typed, tested codebase** — DTOs, value objects, actions, and an action-based architecture, covered by Pest, Larastan/PHPStan, and Pint

## Tech Stack

| Layer          | Technology                                              |
| -------------- | -------------------------------------------------------- |
| Backend        | [Laravel 13](https://laravel.com) (PHP 8.4)               |
| Frontend       | [Livewire 4](https://livewire.laravel.com) + [Flux UI](https://fluxui.dev) |
| Styling        | [Tailwind CSS 4](https://tailwindcss.com)                 |
| Markdown editor| [EasyMDE](https://github.com/Ionaru/easy-markdown-editor) |
| Search         | [Laravel Scout](https://laravel.com/docs/scout)            |
| Database       | SQLite (default), configurable                            |
| Build tool     | [Vite](https://vitejs.dev)                                 |
| Testing        | [Pest](https://pestphp.com)                                 |
| Static analysis| [Larastan](https://github.com/larastan/larastan) / PHPStan |
| Code style     | [Laravel Pint](https://laravel.com/docs/pint)               |
| Automated refactoring | [Rector](https://getrector.com) (with `driftingly/rector-laravel`) |

## Requirements

- PHP >= 8.3 (project targets 8.4)
- Composer 2
- Node.js 22+ and npm
- SQLite (or another database supported by Laravel)

## Getting Started

Clone the repository:

```bash
git clone https://github.com/davigfarias/river-notetaker.git
cd river-notetaker
```

Run the automated setup (installs PHP & JS dependencies, creates `.env`, generates the app key, runs migrations, and builds assets):

```bash
composer setup
```

Or set things up manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

### Running the app

This project is designed to run on [Laravel Herd](https://herd.laravel.com), which serves it automatically at `https://river-notetaker.test`.

If you're not using Herd, start the built-in dev environment (server, queue listener, and Vite) with:

```bash
composer dev
```

This runs `php artisan dev`, which boots the PHP server, queue worker, and the Vite dev server concurrently.

## Configuration

Key environment variables (see [`.env.example`](.env.example)):

| Variable         | Description                                      | Default              |
| ---------------- | ------------------------------------------------- | --------------------- |
| `APP_NAME`       | Application name                                    | `River Notetaker`      |
| `APP_LOCALE`     | Application locale                                  | `pt_BR`                |
| `DB_CONNECTION`  | Database driver                                     | `sqlite`               |
| `SCOUT_DRIVER`   | Search driver used by Laravel Scout (`database`, `algolia`, `meilisearch`, `typesense`, `collection`) | `collection` |
| `QUEUE_CONNECTION` | Queue driver                                      | `database`              |
| `SESSION_DRIVER` | Session driver                                      | `database`              |

## Application Structure

The codebase favors small, single-responsibility **Actions** orchestrated by the Livewire components, backed by typed **DTOs** and **Value Objects**:

```
app/
├── Actions/            # Single-purpose use cases (GetDisciplines, SaveNote, SearchConcept, ...)
│   ├── Orchestrators/  # Actions that compose other actions/sub-actions
│   └── SubActions/     # Smaller building blocks used by orchestrators
├── DTO/                 # Data Transfer Objects passed between Livewire components and actions
├── Enums/                # DisciplineIcon, ReferencesIcon
├── Models/               # Eloquent models (Notes, Disciplines, Concepts, PastoralAdvices, References, Tags)
├── Repository/           # AppRepository — persistence layer used by actions
├── Support/              # Outcome — a typed success/failure result wrapper
└── ValueObjects/         # Date and other typed value objects with custom Eloquent casts

resources/views/pages/    # Livewire single-file page components (dashboard, create, disciplina, concepts)
routes/web.php            # Route::livewire(...) definitions
```

### Routes

| Route                    | Component            | Description                              |
| ------------------------- | --------------------- | ----------------------------------------- |
| `GET /`                    | `pages::dashboard`     | List and manage disciplines                |
| `GET /disciplinas/{slug}`  | `pages::disciplina`    | Browse notes within a discipline           |
| `GET /notas/nova`          | `pages::create`        | Create a new note                          |
| `GET /conceitos/lista`     | `pages::concepts`      | Browse/search the concepts glossary        |

## Testing & Code Quality

Run the full CI suite (config clear, lint check, static analysis, tests) exactly as it runs in GitHub Actions:

```bash
composer ci:check
```

Or run each check individually:

```bash
# Tests (Pest)
php artisan test --compact

# Code style (Pint)
composer lint          # fix
composer lint:check    # check only

# Static analysis (Larastan/PHPStan)
composer types:check
```

Continuous integration runs on every push and pull request to `main` via [GitHub Actions](.github/workflows/tests.yml).

## Contributing

1. Fork the repository and create your branch from `main`.
2. Make your changes, following the existing code conventions.
3. Run `composer ci:check` before opening a pull request.
4. Open a pull request describing your changes.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
