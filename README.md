# Untitled CMS

A clean, modular, and extensible Laravel-based content management system designed for developers and content creators.

[![Tests](https://img.shields.io/badge/tests-25%20passing-brightgreen)](tests/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue)](https://php.net)

## ✨ Features

- **Modern Stack**: Laravel 12 + Blade + Tailwind CSS + Alpine.js
- **Authentication**: Complete auth system with Laravel Breeze
- **Admin Dashboard**: Clean, responsive admin interface
- **Content Management**: Pages, media, and navigation management
- **User Roles**: Super Admin, Admin, and Editor roles with policies
- **Developer Friendly**: Built with extensibility and modularity in mind
- **Quality Assurance**: Pest testing framework, PHPStan, and Laravel Pint

## 🚀 Quick Start

### Requirements

- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL 8.0+ or SQLite

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd untitled-cms
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run dev
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to see your CMS in action!

## 🏗️ Architecture

### Project Structure

```
app/
├── Domain/              # Business logic domains
├── Http/               # Controllers, requests, middleware
└── View/               # View components

resources/
├── views/
│   ├── admin/          # Admin interface views
│   ├── auth/           # Authentication views
│   └── layouts/        # Base layouts
├── css/                # Stylesheets
└── js/                 # JavaScript

routes/
├── web.php            # Public routes
├── admin.php          # Admin routes
└── auth.php           # Authentication routes
```

### Tech Stack

- **Backend**: Laravel 12.x with PHP 8.2+
- **Frontend**: Blade templating with Tailwind CSS and Alpine.js
- **Database**: MySQL/SQLite with Eloquent ORM
- **Authentication**: Laravel Breeze
- **Testing**: Pest with Laravel plugins
- **Code Quality**: Laravel Pint (PSR-12) and PHPStan (Level 5)

## 🧪 Development

### Running Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

### Code Quality

```bash
# Format code
./vendor/bin/pint

# Check formatting without fixing
./vendor/bin/pint --test

# Static analysis
./vendor/bin/phpstan analyse
```

### Asset Building

```bash
# Development (watch mode)
npm run dev

# Production build
npm run build
```

## 📖 Documentation

- **[Project Plan](PROJECT_PLAN.md)**: Complete development roadmap and technical specifications
- **[Initial Requirements](init.md)**: Original project brief and requirements

## 🛡️ Security

- CSRF protection enabled
- XSS prevention with Blade escaping
- SQL injection protection via Eloquent ORM
- Password hashing with bcrypt
- Rate limiting on authentication routes

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation as needed
- Ensure all quality checks pass

## 📋 Roadmap

### Phase 1 - Core CMS ✅
- [x] Authentication system
- [x] Admin dashboard
- [x] Basic project structure
- [ ] Site settings management
- [ ] Page management with content blocks
- [ ] Media management
- [ ] Navigation builder
- [ ] User roles and permissions

### Phase 2 - Developer Tools 🔮
- [ ] IP and geo location services
- [ ] Network monitoring tools
- [ ] DNS management
- [ ] System status monitoring

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙋‍♂️ Support

- **Issues**: [GitHub Issues](../../issues)
- **Discussions**: [GitHub Discussions](../../discussions)
- **Documentation**: See [PROJECT_PLAN.md](PROJECT_PLAN.md) for detailed technical documentation
