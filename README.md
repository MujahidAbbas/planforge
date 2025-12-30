# PlanForge

> AI-powered project planning: from idea to PRD to Tech Spec to Tasks in minutes.

[![CI](https://github.com/MujahidAbbas/planforge/actions/workflows/ci.yml/badge.svg)](https://github.com/MujahidAbbas/planforge/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

PlanForge is a self-hosted project planning tool that uses AI to generate comprehensive project documentation from a simple idea. It creates Product Requirements Documents (PRDs), Technical Specifications, and actionable tasks - all organized in a Kanban board.

## Features

- **AI-Powered Generation** - Transform project ideas into structured documentation
- **PRD Generation** - Automatic Product Requirements Document creation
- **Tech Spec Generation** - Detailed technical specifications based on the PRD
- **Kanban Board** - Drag-and-drop task management with Flowforge
- **Regeneration** - Regenerate individual documents or the entire pipeline
- **Export** - Download project kit as ZIP (PRD, Tech Spec, Tasks, metadata)
- **Rate Limit Resilience** - Graceful handling of API rate limits with automatic retries

## Tech Stack

- **Backend**: Laravel 12, PHP 8.3+
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **AI**: PrismPHP (Anthropic Claude, OpenAI, and more)
- **Kanban**: Filament 4 + Flowforge
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Queue**: Database driver (Redis optional)

## Quick Start

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- An Anthropic API key ([get one here](https://console.anthropic.com/))

### Installation

```bash
# Clone the repository
git clone https://github.com/MujahidAbbas/planforge.git
cd planforge

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Add your Anthropic API key to .env
# ANTHROPIC_API_KEY=sk-ant-...

# Create database and run migrations
touch database/database.sqlite
php artisan migrate

# Build frontend assets
npm run build

# Seed demo data (optional)
php artisan db:seed --class=DemoSeeder
```

### Running the Application

```bash
# Start all services (server, queue, logs, vite)
composer dev

# Or run individually:
php artisan serve          # Web server
php artisan queue:listen   # Queue worker (required for AI generation)
npm run dev               # Vite dev server
```

Visit `http://localhost:8000/projects` to get started.

## Usage

1. **Create a Project** - Enter your project idea and any constraints
2. **Generate** - Click "Generate All" to create PRD, Tech Spec, and Tasks
3. **Review & Edit** - Edit the generated documents in the PRD and Tech tabs
4. **Manage Tasks** - Drag tasks between columns in the Kanban board
5. **Regenerate** - Use the Regenerate dropdown to refresh specific documents
6. **Export** - Download your project kit as a ZIP file

## Project Structure

```
app/
├── Actions/           # Business logic (StartPlanRun, Regenerate*)
├── Enums/            # Status enums (PlanRunStatus, TaskStatus, etc.)
├── Jobs/             # Queue jobs (GeneratePrdJob, GenerateTechSpecJob)
├── Livewire/         # Livewire components
│   └── Projects/
│       ├── Workspace.php
│       └── Tabs/     # PRD, Tech, Kanban, Export tabs
├── Models/           # Eloquent models
└── Services/         # Services (ProjectKitExporter)

resources/
├── views/
│   ├── livewire/     # Livewire component views
│   └── prompts/      # AI prompt templates
└── css/              # Tailwind styles
```

## Testing

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/ExportTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

## Configuration

### AI Provider

PlanForge uses Anthropic Claude by default. Configure in `.env`:

```env
ANTHROPIC_API_KEY=sk-ant-...
```

### Queue Driver

For production, consider using Redis:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

## Roadmap

- [ ] Task generation from Tech Spec
- [ ] GitHub Issues export
- [ ] Multiple AI provider support
- [ ] Team collaboration
- [ ] Project templates

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Security

If you discover a security vulnerability, please see [SECURITY.md](SECURITY.md) for reporting instructions.

## License

PlanForge is open-source software licensed under the [MIT license](LICENSE).
