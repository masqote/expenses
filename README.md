# Expense Tracker — Laravel API

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+

## Setup

```bash
# Install dependencies
composer install

# Copy and configure environment
cp .env.example .env
# Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

## Testing

```bash
# Run all tests with Pest
./vendor/bin/pest

# Run a specific test file
./vendor/bin/pest tests/Feature/AuthTest.php
```

## Tech Stack

- **Framework**: Laravel 11
- **Auth**: Laravel Sanctum (token-based)
- **Database**: MySQL (InnoDB, utf8mb4)
- **Test runner**: Pest PHP
