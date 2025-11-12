# ChurchEase

A modern church management platform built with Laravel 12 and React 19, designed to streamline church administration and member management.

## Overview

ChurchEase is a comprehensive church management system that provides role-based access control, secure authentication, and a beautiful user interface. The platform supports multiple administrative roles including Super Administrators and Church Administrators, each with tailored dashboards and permissions.

## Key Features

- **Role-Based Access Control**: Multi-level permissions with Super Admin and Church Admin roles powered by Spatie Laravel Permission
- **Secure Authentication**: Complete authentication system with:
  - User registration and login
  - Email verification
  - Password reset functionality
  - Two-Factor Authentication (2FA) with QR codes
  - Password confirmation for sensitive operations
- **Modern UI/UX**:
  - Beautiful, responsive interface built with Tailwind CSS 4
  - Dark mode support across all pages
  - Radix UI components for accessibility
  - Type-safe routing with Laravel Wayfinder
- **Queue Management**: Laravel Horizon for monitoring and managing background jobs
- **Real-time Development**: Hot module replacement with Vite and Inertia SSR support
- **Developer Experience**: Comprehensive testing with Pest, code formatting with Pint, and ESLint/Prettier for frontend

## Tech Stack

### Backend
- **PHP**: 8.2+
- **Laravel Framework**: 12.x
- **Inertia.js Laravel**: 2.x
- **Laravel Fortify**: 1.30+ (Authentication)
- **Laravel Horizon**: 5.39+ (Queue Management)
- **Laravel Wayfinder**: 0.1.9+ (Type-safe routing)
- **Spatie Laravel Permission**: 6.23+ (Role management)

### Frontend
- **React**: 19.2.0
- **TypeScript**: 5.7.2
- **Inertia.js React**: 2.1.4+
- **Tailwind CSS**: 4.x
- **Radix UI**: Component library
- **Vite**: 7.x
- **Lucide React**: Icon library

### Development Tools
- **Pest**: 4.x (Testing framework)
- **Laravel Pint**: 1.x (Code formatting)
- **ESLint**: 9.x
- **Prettier**: 3.x
- **Laravel Boost**: 1.7+ (Development MCP server)

### Infrastructure
- **PostgreSQL**: 17 (Database)
- **Redis**: 7 (Cache & Queue)
- **Nginx**: 1.27 (Web server)
- **Mailpit**: Email testing
- **Adminer**: Database management UI
- **Docker**: Containerized development environment

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Node.js**: 18+ and npm
- **Docker & Docker Compose**: For containerized development (recommended)

**OR** for local development without Docker:
- **PostgreSQL**: 17+
- **Redis**: 7+

## Installation

### Option 1: Docker Development (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd churchease
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies and setup**
   ```bash
   docker-compose exec app composer setup
   ```

   This command will:
   - Install PHP dependencies
   - Create `.env` file (if not exists)
   - Generate application key
   - Run database migrations
   - Install Node.js dependencies
   - Build frontend assets

5. **Seed the database** (optional - creates test users)
   ```bash
   docker-compose exec app php artisan db:seed
   ```

   Default test accounts:
   - **Super Admin**: superadmin@test.com / 12345678
   - **Church Admin**: churchadmin@test.com / 12345678

6. **Access the application**
   - **Application**: http://localhost:8000
   - **Mailpit (Email testing)**: http://localhost:8025
   - **Adminer (Database UI)**: http://localhost:8080
   - **Horizon (Queue dashboard)**: http://localhost:8888

### Option 2: Local Development

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd churchease
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**

   Update your `.env` file with your PostgreSQL credentials:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=churchease
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Configure Redis**

   Update your `.env` file with Redis settings:
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

7. **Run migrations**
   ```bash
   php artisan migrate
   ```

8. **Seed the database** (optional)
   ```bash
   php artisan db:seed
   ```

9. **Start development servers**
   ```bash
   composer run dev
   ```

   This will concurrently run:
   - Laravel development server (port 8000)
   - Queue worker
   - Laravel Pail (log viewer)
   - Vite dev server (port 5173)

   **Alternative**: Run servers individually
   ```bash
   # Terminal 1
   php artisan serve

   # Terminal 2
   npm run dev

   # Terminal 3
   php artisan queue:listen
   ```

## Project Structure

```
churchease/
├── app/
│   ├── Console/           # Artisan commands
│   ├── Enums/            # Application enums (UserRolesEnum, etc.)
│   ├── Http/
│   │   ├── Controllers/  # HTTP controllers
│   │   └── Requests/     # Form request validation
│   ├── Models/           # Eloquent models
│   └── Providers/        # Service providers
├── bootstrap/            # Application bootstrap files
├── config/              # Configuration files
├── database/
│   ├── factories/       # Model factories for testing
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
├── public/              # Public assets
├── resources/
│   ├── css/            # Global stylesheets
│   └── js/
│       ├── components/  # React components
│       ├── layouts/     # Page layouts
│       ├── pages/       # Inertia pages
│       ├── types/       # TypeScript type definitions
│       ├── app.tsx      # Frontend entry point
│       └── ssr.tsx      # SSR entry point
├── routes/
│   ├── console.php      # Console routes
│   ├── settings.php     # Settings routes
│   └── web.php          # Web routes
├── storage/             # Application storage
├── tests/
│   ├── Feature/         # Feature tests
│   └── Unit/            # Unit tests
└── vendor/              # Composer dependencies
```

## Development Workflow

### Common Commands

**Start development environment** (Docker):
```bash
docker-compose up -d
docker-compose exec app composer run dev
```

**Start development environment** (Local):
```bash
composer run dev
```

**Run migrations**:
```bash
php artisan migrate
```

**Create a new migration**:
```bash
php artisan make:migration create_table_name --no-interaction
```

**Create a new model with factory and migration**:
```bash
php artisan make:model ModelName -mf --no-interaction
```

**Generate Wayfinder routes** (after route changes):
```bash
php artisan wayfinder:generate
```

**Format PHP code**:
```bash
vendor/bin/pint
```

**Format frontend code**:
```bash
npm run format
```

**Lint frontend code**:
```bash
npm run lint
```

**Type check TypeScript**:
```bash
npm run types
```

**Build production assets**:
```bash
npm run build
```

**Build with SSR support**:
```bash
npm run build:ssr
```

## Testing

ChurchEase uses Pest for testing with both Unit and Feature test suites.

**Run all tests**:
```bash
php artisan test
```

**Run specific test file**:
```bash
php artisan test tests/Feature/ExampleTest.php
```

**Run tests with filter**:
```bash
php artisan test --filter=test_name
```

**Run tests with coverage**:
```bash
php artisan test --coverage
```

## Authentication Features

ChurchEase includes comprehensive authentication powered by Laravel Fortify:

- **Registration**: New user registration with email verification
- **Login/Logout**: Secure authentication with rate limiting
- **Password Reset**: Email-based password reset flow
- **Email Verification**: Required email verification for new accounts
- **Two-Factor Authentication**:
  - QR code generation for authenticator apps
  - Recovery codes
  - Password confirmation requirement
  - OTP confirmation before enabling

## Queue Management

Laravel Horizon provides a beautiful dashboard for monitoring Redis queues.

**Access Horizon**:
- **Docker**: http://localhost:8888
- **Local**: http://localhost:8000/horizon

**Start Horizon** (local development):
```bash
php artisan horizon
```

## Docker Services

When running with Docker, the following services are available:

| Service | Description | Port | URL |
|---------|-------------|------|-----|
| app | PHP-FPM application | - | - |
| nginx | Web server | 8000 | http://localhost:8000 |
| postgres | PostgreSQL database | 5432 | - |
| redis | Redis cache/queue | 6379 | - |
| mailpit | Email testing | 8025 | http://localhost:8025 |
| adminer | Database UI | 8080 | http://localhost:8080 |
| horizon | Queue dashboard | 8888 | http://localhost:8888 |
| ssr | Inertia SSR server | 13714 | - |
| scheduler | Laravel scheduler | - | - |

**Useful Docker commands**:
```bash
# View logs
docker-compose logs -f app

# Access app container shell
docker-compose exec app bash

# Restart services
docker-compose restart

# Stop all services
docker-compose down

# Stop and remove volumes
docker-compose down -v
```

## Environment Configuration

Key environment variables to configure:

```env
# Application
APP_NAME=ChurchEase
APP_ENV=local
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Manila

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres  # or 127.0.0.1 for local
DB_PORT=5432
DB_DATABASE=churchease
DB_USERNAME=churchease
DB_PASSWORD=churchease

# Redis
REDIS_HOST=redis  # or 127.0.0.1 for local
REDIS_PASSWORD=churchease
REDIS_PORT=6379

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (Development)
MAIL_MAILER=smtp
MAIL_HOST=mailpit  # or localhost for local
MAIL_PORT=1025

# Inertia SSR
INERTIA_SSR_ENABLED=true
INERTIA_SSR_URL=http://ssr:13714  # or http://localhost:13714 for local
```

## Code Style

This project follows Laravel and React best practices:

### PHP
- Follow PSR-12 coding standards
- Use Laravel Pint for automatic formatting: `vendor/bin/pint`
- Write PHPDoc blocks for classes and methods
- Use strict types and return type declarations

### TypeScript/React
- Use TypeScript for type safety
- Format with Prettier: `npm run format`
- Lint with ESLint: `npm run lint`
- Use functional components with hooks
- Organize imports with prettier-plugin-organize-imports

## Troubleshooting

**Frontend changes not reflecting?**
- Ensure Vite dev server is running: `npm run dev`
- Clear browser cache
- For Docker: Check that port 5173 is properly forwarded

**Database connection issues?**
- Verify PostgreSQL is running
- Check credentials in `.env` file
- For Docker: Ensure postgres container is healthy: `docker-compose ps`

**Queue jobs not processing?**
- Ensure Redis is running
- For Docker: Check horizon container: `docker-compose logs horizon`
- For local: Start queue worker: `php artisan queue:listen`

**Permission errors in Docker?**
- Check UID/GID in `.env` match your system user
- Fix permissions: `docker-compose exec app chown -R www-data:www-data storage bootstrap/cache`

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes and write tests
4. Ensure all tests pass: `php artisan test`
5. Format your code: `vendor/bin/pint` and `npm run format`
6. Commit your changes with descriptive messages
7. Push to your fork and submit a pull request

## License

[Specify your license here]

## Support

For issues, questions, or contributions, please [open an issue](link-to-issues) on GitHub.

---

Built with ❤️ using Laravel, React, and modern web technologies.
