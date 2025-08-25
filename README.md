<p align="center">
  <h1 align="center">MU Connect Backend</h1>
</p>

<p align="center">
  <strong>A comprehensive university platform connecting students through resources, section exchanges, and AI-powered learning tools</strong>
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.0-red" alt="Laravel Version">
<img src="https://img.shields.io/badge/PHP-8.2+-blue" alt="PHP Version">
<img src="https://img.shields.io/badge/License-MIT-green" alt="License">
</p>

## About MU Connect

MU Connect is a modern university platform built with Laravel that enhances student collaboration and learning through:

- **📚 Resource Sharing** - Students can upload and share academic materials organized by faculty, major, and course
- **🔄 Section Exchange** - Seamless system for students to request and exchange course sections
- **🤖 AI-Powered Learning** - Integration with Gemini AI for quiz generation and document summarization
- **🔔 Real-time Notifications** - Keep students updated on applications, requests, and system activities
- **👥 User Management** - Comprehensive authentication with role-based permissions using Spatie Laravel Permission
- **📱 API Documentation** - Auto-generated API docs with Laravel Request Docs

## Key Features

### 🎯 Core Functionality
- **Student Authentication** with Laravel Sanctum
- **Faculty/Major/Course Organization** with proper relationships
- **File Upload & Management** for academic resources
- **Section Request & Application System** with status tracking
- **Real-time Notification System** with read/unread states

### 🤖 AI Integration
- **Quiz Generation** from uploaded documents (PDF, images, text)
- **Document Summarization** with multiple formats (concise, detailed, bullet points, key concepts)
- **Caching System** for AI responses to optimize performance
- **File Type Validation** and size limits for security

### 🔐 Security & Performance
- **Rate Limiting** on API endpoints
- **Input Validation** with custom Form Requests
- **Soft Deletes** for data integrity
- **Database Indexing** for optimized queries
- **Redis Caching** for improved performance

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/mu-connect-backend.git
   cd mu-connect-backend
   ```

2. **Install dependencies**
   ```bash
   composer install
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

5. **Storage linking**
   ```bash
   php artisan storage:link
   ```

6. **Install Laravel Sanctum**
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

## Environment Configuration

```bash
# Application
APP_NAME="MU Connect"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mu_connect
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# AI Integration (Gemini)
GEMINI_API_KEY=your_gemini_api_key

# File Storage
FILESYSTEM_DISK=local
```

## API Documentation

The API documentation is automatically generated and available at:
```
http://localhost:8000/request-docs
```

This includes all endpoints for:
- Authentication
- User management
- Resource management
- Section requests & applications
- Notifications
- AI services (quiz generation, summarization)

## Database Schema

### Core Tables
- **users** - Student profiles with faculty/major relationships
- **faculties** - University faculties
- **majors** - Academic majors linked to faculties
- **courses** - Course catalog linked to majors
- **resources** - Academic materials shared by students
- **section_requests** - Section exchange requests
- **applications** - Applications to section requests
- **notifications** - Real-time notification system
- **personal_access_tokens** - API token management

## API Endpoints Overview

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Resources
- `GET /api/resources` - List resources with filtering
- `POST /api/resources` - Upload new resource
- `PUT /api/resources/{id}` - Update resource
- `DELETE /api/resources/{id}` - Delete resource

### Section Exchange
- `GET /api/section-requests` - List section requests
- `POST /api/section-requests` - Create section request
- `POST /api/applications` - Apply to section request
- `PUT /api/applications/{id}` - Update application status

### Notifications
- `GET /api/notifications` - Get user notifications
- `PUT /api/notifications/{id}/read` - Mark as read
- `DELETE /api/notifications/{id}` - Delete notification

### AI Services
- `POST /api/ai/generate-quiz` - Generate quiz from file
- `POST /api/ai/generate-summary` - Generate summary from file

## Development

### Running the Application
```bash
php artisan serve
```

### Running Tests
```bash
php artisan test
```

### Code Formatting
```bash
./vendor/bin/pint
```

### Queue Processing
```bash
php artisan queue:work
```

## Security Features

- **CSRF Protection** on all state-changing operations
- **Rate Limiting** (60 requests per minute on notifications)
- **File Upload Validation** with MIME type checking
- **SQL Injection Prevention** through Eloquent ORM
- **XSS Protection** with proper input sanitization
- **Authentication** required for all protected routes

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team

## Acknowledgments

- **Laravel Framework** - The foundation of our application
- **Spatie Laravel Permission** - Role and permission management
- **Laravel Sanctum** - API authentication
- **Gemini AI** - AI-powered features
- **Laravel Request Docs** - API documentation generation