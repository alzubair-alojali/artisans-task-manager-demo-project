<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<h1 align="center">Artisans Task Manager API</h1>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2"></a>
  <a href="https://www.docker.com"><img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker"></a>
  <a href="https://www.postgresql.org"><img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL"></a>
</p>

---

## 📋 Project Overview

**Artisans Task Manager** is a robust **JSON RESTful API** built to demonstrate advanced backend capabilities using **Laravel 12**. 

This project was developed as a technical assessment to showcase:
- **Complex Database Relationships** (One-to-One, One-to-Many, Polymorphic).
- **Role-Based Access Control (RBAC)** using Policies and Gates.
- **Secure Authentication** via Laravel Sanctum & Google OAuth (Socialite).
- **Performance Optimization** through caching and eager loading.
- **Code Quality** following Laravel best practices and SOLID principles.

---

## 🎯 Business Scenario

### The Scenario
The API manages a collaborative environment where organizations manage work through **Projects** and **Tasks**. The system solves the problem of tracking task progress, enforcing deadlines, and maintaining clear permission boundaries between different hierarchy levels.

### Resources & Relationships
To fulfill the requirements, the following entities were modeled:

1.  **Users**: The system actors.
    * *Relationship:* Many-to-Many with Projects (Members).
2.  **Projects**: Containers for tasks.
    * *Relationship:* One-to-Many with Tasks.
    * *Relationship:* Belongs-to One Manager (User).
3.  **Tasks**: Actionable items with status and priority.
    * *Relationship:* Belongs-to Project and Assigned User.
    * *Self-Referencing:* (Optional) Parent/Child tasks capability.
4.  **Comments**: A feedback system.
    * *Advanced Modeling:* **Polymorphic Relationship** (One-to-Many Polymorphic). Comments can be attached to both `Projects` and `Tasks` using the same table.

---

## 🛠️ Tech Stack & Libraries

The following libraries were chosen to enhance the solution as per the "Bonus" and "Requirements" guidelines:

| Package | Purpose & Justification |
|---------|-------------------------|
| **`laravel/sanctum`** | **Authentication:** Implements lightweight, secure token-based authentication required for the API. |
| **`spatie/laravel-permission`** | **Authorization:** Handles complex Role-Based Access Control (Admin, Manager, User) efficiently without cluttering the core logic. |
| **`spatie/laravel-query-builder`** | **Filtering:** Fulfills the requirement for "Index listing endpoints that support filters". It securely allows filtering (`?filter[status]`), sorting, and including relationships. |
| **`laravel/socialite`** | **Bonus Feature:** Implements "Sign in with Google" as requested in the bonus points. |
| **`dedoc/scramble`** | **Documentation:** Automatically generates OpenAPI/Swagger documentation to make the API explorable. |
| **`spatie/simple-excel`** | **Data Export:** Used to implement the "Excel Export" feature for Tasks, using streams for memory efficiency. |

---

## � Installation & Setup Instructions

### Prerequisites
- PHP >= 8.2
- Composer
- PostgreSQL or MySQL
- Docker (Optional)

### 1. Clone the Repository
```bash
git clone https://github.com/alzubair-alojali/artisans-task-manager-api.git
cd artisans-task-manager-api
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
Copy the example environment file and configure your database credentials.

```bash
cp .env.example .env
php artisan key:generate
```

**Crucial `.env` Settings:**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=artisans_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Google OAuth (For Bonus Feature)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

### 4. Database Setup (Migrations & Seeders)
This command will create the table structure and populate the database with dummy data, including a default Admin account.

```bash
php artisan migrate --seed
```

**Default Admin Credentials:**
- **Email:** `admin@artisans.ly`
- **Password:** `password`

### 5. Running the Application

**Option A: Local Server**
```bash
php artisan serve
```
The API will be available at: `http://localhost:8000/api`

**Option B: Docker**

```bash
# Build the Docker image
docker build -t artisans-api .

# Run with environment file (recommended)
docker run -p 8080:8080 --env-file .env artisans-api

# Or run with inline environment variables
docker run -p 8080:8080 \
  -e APP_KEY=base64:your_app_key \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=5432 \
  -e DB_DATABASE=artisans_db \
  -e DB_USERNAME=your_user \
  -e DB_PASSWORD=your_pass \
  artisans-api
```

The API will be available at: `http://localhost:8080/api`

> **Note:** The container uses port `8080` internally. When deployed to Render, the `$PORT` environment variable is automatically used.

## 📚 API Documentation

### Interactive Swagger Docs
Access the auto-generated API documentation to explore endpoints and payloads:

**URL:** `http://localhost:8000/docs/api`

### Postman Collection
A comprehensive Postman collection containing success and failure test cases is included in the repository.

- **File:** `Artisans_Collection.json`
- **Import:** Open Postman → Import → Upload the file.

---

## 🛡️ Security & Access Control

The system implements strict RBAC:

| Role | Permissions |
|------|-------------|
| **Admin** | Full access to all resources (Users, Projects, Tasks). |
| **Manager** | Can manage only their own projects and tasks within them. |
| **User** | Read-only access to projects they are members of; can update tasks assigned to them or pick up unassigned tasks. |

**Security Measures:**
- `sanctum` middleware on all protected routes.
- Policies applied to Controllers for authorization checks.
- Input validation using FormRequests (422 responses).
- Strict Types enforced across the codebase.

---

## 🧪 Testing

To run the automated tests suite:

```bash
php artisan test
```

---

## 🌐 Live Deployment (Bonus)

The API is deployed live on Render.

**Base URL:** `https://artisans-task-manager.onrender.com/api`

---

<p align="center">
  Submitted by <strong>Al-Zubair Al-Ojali</strong> for Artisans Backend Developer Position.
</p>
