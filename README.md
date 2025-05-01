# EventWithMe API

EventWithMe is a RESTful API built with Symfony 7.

## Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Configure your database in `.env.local`:
   ```
   DATABASE_URL="mysql://username:password@localhost:3306/eventwithme?serverVersion=8.0.32&charset=utf8mb4"
   ```
4. Create database and run migrations:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
5. Start the development server:
   ```bash
   symfony serve
   ```

## API Endpoints

### Base Endpoints
- `GET /api` - API information
- `GET /api/health` - API health check

### Authentication Endpoints
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get JWT token
- `GET /api/profile` - Get user profile (requires authentication)

## User Data Structure

```json
{
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "password": "securePassword"
}
```

## Development

This project uses Doctrine ORM for database management and Symfony's serializer for JSON responses.
