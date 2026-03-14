# Contributing to Laravel Page Builder

Thank you for your interest in contributing to **Laravel Page Builder**! Every
contribution helps — whether it's reporting a bug, suggesting a feature,
improving documentation, or submitting a pull request.

Please take a moment to review this guide before getting started.

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md).
By participating, you are expected to uphold this code. Please report
unacceptable behavior to [hello@dipaksarkar.in](mailto:hello@dipaksarkar.in).

## How Can I Contribute?

### Reporting Bugs

Before opening an issue, please search [existing issues](../../issues) to make
sure the problem has not already been reported.

When filing a bug report, include:

- **PHP and Laravel versions** (`php -v`, `php artisan --version`)
- **Package version** (`composer show coderstm/laravel-page-builder`)
- **Steps to reproduce** — a minimal, reproducible example is ideal
- **Expected vs. actual behavior**
- **Error messages or logs** (redact any sensitive data)

### Suggesting Features

Feature ideas are welcome! Open a [new issue](../../issues/new) with:

- A clear and descriptive title
- The problem you're trying to solve
- Your proposed solution or approach
- Any alternatives you've considered

### Improving Documentation

Docs improvements are always appreciated. This includes:

- Fixing typos or unclear language
- Adding code examples
- Updating outdated information
- Translating documentation

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & Yarn (for the editor frontend)

### Getting Started

1. **Fork & clone the repository**

   ```bash
   git clone https://github.com/<your-username>/laravel-page-builder.git
   cd laravel-page-builder
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Install frontend dependencies**

   ```bash
   yarn install
   ```

4. **Run the test suite**

   ```bash
   composer test
   ```

5. **Start the development server** (optional, for manual testing)

   ```bash
   composer serve
   ```

## Pull Request Guidelines

### Before Submitting

- **Create a branch** from `main` for your changes:
  ```bash
  git checkout -b feature/your-feature-name
  ```
- **Follow existing code style.** The project uses [Laravel Pint](https://laravel.com/docs/pint) for PHP formatting:
  ```bash
  vendor/bin/pint
  ```
- **Write or update tests** for any new functionality or bug fixes.
- **Run the full test suite** and ensure all tests pass:
  ```bash
  composer test
  ```
- **Keep commits focused.** Each commit should represent a single logical change.

### Submitting Your PR

1. Push your branch to your fork.
2. Open a pull request against the `main` branch.
3. In the PR description, include:
   - **What** the PR does
   - **Why** the change is needed
   - A reference to any related issue (e.g. `Closes #42`)
4. Be responsive to feedback — maintainers may request changes before merging.

### PR Review Criteria

- Code follows the existing project conventions
- Tests pass and new functionality is covered
- Documentation is updated if necessary
- The change does not introduce unnecessary dependencies

## Project Structure

A quick overview to help you navigate the codebase:

```
├── src/             # PHP package source (Service Providers, Registries, Renderers)
├── config/          # Default configuration file
├── database/        # Migrations
├── resources/       # Blade views and React editor source (TypeScript)
├── routes/          # Route definitions
├── dist/            # Compiled editor assets
├── tests/           # PHPUnit test suite
├── workbench/       # Testbench workbench app for local development
└── docs/            # Developer documentation
```

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use strict types where possible
- Add PHPDoc blocks for public methods
- Run `vendor/bin/pint` before committing

### TypeScript / React (Editor)

- Follow the existing code style in `resources/`
- Use functional components and hooks
- Keep components focused and reusable

## Testing

The project uses [PHPUnit](https://phpunit.de/) with
[Orchestra Testbench](https://github.com/orchestral/testbench):

```bash
# Run all tests
composer test

# Run a specific test file
vendor/bin/phpunit tests/Feature/YourTest.php

# Run a specific test method
vendor/bin/phpunit --filter test_your_method
```

## License

By contributing, you agree that your contributions will be licensed under the
same [Non-Commercial Open Source License](../LICENSE.md) that covers the project.

---

Thank you for helping make Laravel Page Builder better! 🚀
