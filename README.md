# EventWithMe API

EventWithMe is a RESTful API built with Symfony 7.2, featuring JWT authentication, refresh tokens, social login (Google, Facebook, Apple), password reset flows, API versioning, CORS, and Swagger UI documentation.

## Features

- JWT authentication with custom claims and refresh tokens
- Social login via Google, Facebook, and Apple (OAuth and mobile token flows)
- Password reset: request, validate token, and reset endpoints
- API versioning via URL or headers
- Rate limiting and cache headers for API responses
- CORS configured for local development and configurable origins
- Auto-generated API docs via Swagger UI (`/api/doc`)

## Requirements

- PHP `>= 8.2`
- Composer
- Symfony CLI (recommended)
- PostgreSQL 17 (default via Docker) or MySQL 8 (optional)
- Docker (optional, provided compose setup)

## Setup (Local)

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Generate JWT keys (required for login):
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```
   This creates `config/jwt/private.pem` and `config/jwt/public.pem`. Ensure `JWT_PASSPHRASE` is set in your environment.
4. Create `.env.local` based on `.env.dist` and configure environment:
   - Default Docker Postgres:
     ```env
     DATABASE_URL="postgresql://root:root@eventwithme-postgres:5432/eventwithme?serverVersion=17&charset=utf8"
     ```
   - Or local Postgres:
     ```env
     DATABASE_URL="postgresql://user:password@localhost:5432/eventwithme?serverVersion=17&charset=utf8"
     ```
   - Or MySQL (if you prefer):
     ```env
     DATABASE_URL="mysql://user:password@localhost:3306/eventwithme?serverVersion=8.0&charset=utf8mb4"
     ```
   - Configure OAuth credentials if using social login (see `.env.dist`).
5. Create database and run migrations:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
6. Start the development server:
   ```bash
   symfony serve
   ```
7. Open API docs:
   ```
   http://localhost:8000/api/doc
   ```

## Setup (Docker)

The repo includes a Docker setup for Traefik, Nginx, PHP-FPM, PostgreSQL, MySQL, RabbitMQ, and pgAdmin.

1. Ensure the external Docker network exists:
   ```bash
   docker network create proxy
   ```
2. Build and start containers:
   ```bash
   make start
   ```
3. Useful commands:
   - Shell into PHP container: `make sh` or `make bash`
   - Apply migrations inside container:
     ```bash
     docker compose exec eventwithme-php php bin/console doctrine:migrations:migrate
     ```
4. Default DB connection (inside containers): `eventwithme-postgres` on port `5432`.

## Authentication

JWT is provided by `lexik/jwt-authentication-bundle` and refresh tokens by `gesdinet/jwt-refresh-token-bundle`. Custom claims are added via an event listener (user id, username, first/last name).

- Login: `POST /api/auth/login`
  - Body:
    ```json
    { "username": "user@example.com", "password": "password123" }
    ```
  - Response (example):
    ```json
    { "token": "<jwt>", "refresh_token": "<refresh-token>" }
    ```

- Register: `POST /api/auth/register`
  - Body:
    ```json
    {
      "email": "user@example.com",
      "username": "johndoe",
      "password": "password123",
      "firstName": "John",
      "lastName": "Doe",
      "phoneNumber": "+901234567890"
    }
    ```
  - Response returns the created user (without password).

- Profile: `GET /api/auth/profile` (requires Bearer token)

- Refresh Token: `POST /api/auth/token/refresh`
  - Body:
    ```json
    { "refresh_token": "<refresh-token>" }
    ```

## Password Reset

- Request reset: `POST /api/auth/password/request-reset`
  - Body: `{ "email": "user@example.com" }`
- Reset password: `POST /api/auth/password/reset`
  - Body: `{ "token": "<reset-token>", "password": "newPassword123" }`
- Validate token: `GET /api/auth/password/validate-token/{token}`

## Social Authentication

Two options are supported:

1) Mobile token flow: `POST /api/auth/social`
   - Body:
     ```json
     {
       "provider": "google",
       "token": "<provider-access-token>",
       "userData": { "name": { "firstName": "John", "lastName": "Doe" } }
     }
     ```
   - Response includes a JWT and user data.

2) OAuth connect flow:
   - Get redirect URL: `POST /api/auth/social/connect` with `{ "provider": "google|facebook|apple" }`
   - Callback endpoints (GET):
     - `GET /api/auth/social/google/check`
     - `GET /api/auth/social/facebook/check`
     - `GET /api/auth/social/apple/check`

Configure client IDs/secrets in `.env.local` (see `.env.dist` placeholders).

## Base Endpoints

- `GET /api` – API information and version metadata
- `GET /api/health` – Health check

## API Versioning

Versioning is handled by a request subscriber. Use one of:

- URL path: `/api/v1.1/auth/profile`
- `Accept` header: `application/json; version=1.1`
- `X-API-Version` header: `1.1`

Deprecated versions add a `Sunset` header and may be blocked after the sunset date.

## Rate Limiting

Requests to `/api` are rate-limited (default: 100 requests per 60 seconds). Responses include:

- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

## CORS

Configured via `nelmio/cors-bundle` using `CORS_ALLOW_ORIGIN` env var. Default allows localhost.

## API Docs

- Swagger UI: `GET /api/doc`
- OpenAPI JSON: `GET /api/doc.json`

## Development Notes

- ORM: Doctrine; entities are mapped via attributes
- Serialization: Symfony Serializer with response formatting
- Security: JWT firewall for `/api`, JSON login at `/api/auth/login`
- Migrations: see `migrations/` and run via console

## Makefile Commands

- `make start` – Build and start containers
- `make up` / `make down` – Start/stop containers
- `make sh` / `make bash` – Shell into PHP container
- `make migration` – Run migrations
- `make cc` – Clear and warm up cache
- `make test` – Run tests (if configured)

## Troubleshooting

- Missing JWT keys: run `php bin/console lexik:jwt:generate-keypair`
- DB connection errors: verify `DATABASE_URL` matches your engine and container names
- CORS issues: adjust `CORS_ALLOW_ORIGIN` in `.env.local`
