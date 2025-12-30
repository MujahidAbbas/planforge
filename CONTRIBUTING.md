# Contributing to PlanForge

Thank you for your interest in contributing to PlanForge! This document provides guidelines and information for contributors.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone.

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported in [Issues](https://github.com/MujahidAbbas/planforge/issues)
2. If not, create a new issue with:
   - A clear, descriptive title
   - Steps to reproduce the bug
   - Expected vs actual behavior
   - Your environment (PHP version, OS, etc.)

### Suggesting Features

1. Check existing issues for similar suggestions
2. Create a new issue with the `enhancement` label
3. Describe the feature and its use case

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Run tests: `./vendor/bin/pest`
5. Run linting: `./vendor/bin/pint`
6. Commit with a clear message
7. Push and create a Pull Request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/MujahidAbbas/planforge.git
cd planforge

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Create database
touch database/database.sqlite
php artisan migrate

# Build assets
npm run build
```

## Coding Standards

### PHP

- Follow PSR-12 coding style
- Use Laravel Pint for formatting: `./vendor/bin/pint`
- Write tests for new features
- Use type hints and return types

### JavaScript/CSS

- Use Tailwind CSS for styling
- Follow Alpine.js conventions for interactivity

### Commits

- Use clear, descriptive commit messages
- Reference issues when applicable: `Fix #123: Description`

## Testing

```bash
# Run all tests
./vendor/bin/pest

# Run specific test
./vendor/bin/pest tests/Feature/ExportTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

## Project Structure

```
app/
├── Actions/        # Single-purpose action classes
├── Enums/          # PHP enums for status values
├── Jobs/           # Queue jobs for AI generation
├── Livewire/       # Livewire components
├── Models/         # Eloquent models
└── Services/       # Service classes

tests/
├── Feature/        # Feature tests
└── Unit/           # Unit tests
```

## Questions?

Feel free to open an issue for any questions about contributing.
