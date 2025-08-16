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
- MySQL 8.0+ (recommended) or SQLite

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

4. **Database configuration**
   
   Update your `.env` file with your database credentials:
   
   **For MySQL (recommended):**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=untitled_cms
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```
   
   **For SQLite (alternative):**
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database.sqlite
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=UserRoleSeeder
   ```

6. **Build assets**
   ```bash
   npm run dev
   ```

7. **Start development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to see your CMS in action!

### Default Login Credentials

After running the seeder, you can log in with:
- **Email**: admin@example.com
- **Password**: password
- **Role**: Super Admin

*Remember to change these credentials in production!*

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
- **WYSIWYG Editor**: CKEditor 4 for rich content editing
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

## 🎛️ Content Management

### WYSIWYG Editor

The CMS includes CKEditor 4 for rich text editing with the following features:

- **Rich Text Formatting**: Bold, italic, underline, colors, fonts
- **Content Blocks**: Paragraphs, headings (H1-H6), lists, quotes
- **Media Support**: Image insertion, links, tables
- **Code Support**: Source code view and special characters
- **SEO Friendly**: Clean HTML output with semantic markup

The editor is automatically initialized on page creation and editing forms. No additional configuration required.

### Page Management

- **Content Types**: Rich text content with CKEditor 4 integration
- **SEO Optimization**: Meta titles, descriptions, and keywords
- **Publishing Workflow**: Draft/Published status with scheduled publishing
- **URL Management**: Automatic slug generation from titles
- **Content Organization**: Hierarchical page structure

### Media Library

- **File Upload**: Drag-and-drop file uploads with validation
- **Media Types**: Support for images, documents, videos, and audio
- **Image Handling**: Automatic dimension detection and thumbnails
- **File Management**: View, edit metadata, copy URLs, and delete files
- **Search & Filter**: Filter by file type and search by filename/description

### User Roles & Permissions

- **Super Admin**: Full system access and user management
- **Admin**: Content management and publishing permissions
- **Editor**: Content creation with limited editing permissions
- **Policy-Based**: Granular permissions using Laravel policies

## 📖 Documentation

- **[Project Plan](PROJECT_PLAN.md)**: Complete development roadmap and technical specifications
- **[Initial Requirements](init.md)**: Original project brief and requirements

## 🛡️ Security

- CSRF protection enabled
- XSS prevention with Blade escaping
- SQL injection protection via Eloquent ORM
- Password hashing with bcrypt
- Rate limiting on authentication routes
- Role-based access control with policies
- File upload validation and secure storage

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

### Phase 1 - Core CMS ✅ COMPLETED
- [x] Authentication system with Laravel Breeze
- [x] Admin dashboard with statistics
- [x] Basic project structure and architecture
- [x] Site settings management
- [x] Page management with CKEditor 4 integration
- [x] Navigation builder with drag-and-drop ordering
- [x] Media upload and management system
- [x] User roles and permissions (Super Admin, Admin, Editor)
- [x] SEO meta fields and URL management
- [x] MySQL integration and database optimization
- [x] Content blocks system foundation
- [x] Draft/Published workflow
- [x] Form validation and security policies

**Status**: Production ready! All core CMS features implemented and tested.

### Phase 2 - Developer Tools 🔮
*Will be developed as a separate repository/project*
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
