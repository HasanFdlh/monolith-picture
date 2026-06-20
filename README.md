# Monolith Picture - Laravel RBAC

## Overview

Monolith Picture merupakan aplikasi berbasis Laravel 13 yang menerapkan Role Based Access Control (RBAC) menggunakan Spatie Laravel Permission.

Fitur utama:

* Authentication (Session Based)
* Login
* Logout
* Forgot Password
* User Management
* Role Based Access Control (RBAC)
* Activity Log (Preparation)
* MySQL Database

---

# Tech Stack

* PHP 8.3
* Laravel 13
* MySQL
* Bootstrap 5
* jQuery AJAX
* Spatie Laravel Permission

---

# Requirements

* PHP >= 8.3
* Composer
* MySQL 8+
* NodeJS (optional untuk Vite)
* Laragon / XAMPP / Docker

---

# Installation

Clone project

```bash
git clone <repository-url>
```

Masuk ke project

```bash
cd monolith-picture
```

Install dependency

```bash
composer install
```

Copy environment

```bash
cp .env.example .env
```

Generate application key

```bash
php artisan key:generate
```

---

# Database Configuration

Edit file `.env`

```env
APP_NAME="Monolith Picture"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monolith
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

---

# Database Migration

Jalankan migration

```bash
php artisan migrate
```

---

# Seeder

Generate Role & Permission

```bash
php artisan db:seed
```

---

# Default Account

Administrator

```
Email    : admin@mail.com
Password : password
```

> Sesuaikan dengan data pada `AdminUserSeeder`.

---

# Project Structure

```
app
├── Http
│   ├── Controllers
│   │   ├── AuthController
│   │   └── UserController
│   │   └── DashboardController
│   │   └── SessionController
│   └── Middleware
│
├── Models
│   └── Booth.php
│   └── Media.php
│   └── Session.php
│   └── Share.php
│   └── User.php
│
database
├── migrations
├── seeders
│   ├── AdminUserSeeder.php
│   └── RolePermissionSeeder.php
│   └── RoleSeeder.php
│   └── SessionSeeder.php
│
resources
└── views
    ├── auth
    ├── dashboard.blade.php
    ├── users
    └── layouts
```

---

# Authentication Flow

```
Login
      │
      ▼
Session Created
      │
      ▼
Dashboard
      │
      ▼
RBAC Checking
      │
      ▼
Authorized Menu
```

---

# Role Based Access Control

Role yang tersedia

* Admin
* User

Permission

```
users.view
users.create
users.update
users.delete
```

Contoh penggunaan

```php
$user->assignRole('Admin');
```

```php
$user->hasRole('Admin');
```

```php
$user->can('users.create');
```

Blade

```blade
@role('Admin')
@endrole

@can('users.create')
@endcan
```

---

# Features

## Authentication

* Login
* Logout
* Forgot Password
* Session Authentication

## Dashboard

* Dashboard setelah login

## User Management

* List User
* Create User
* Update User
* Delete User
* Modal Form
* AJAX CRUD

## Security

* CSRF Protection
* Password Hashing
* Session Authentication
* RBAC Authorization

---

# Running Application

Start development server

```bash
php artisan serve
```

Akses aplikasi

```
http://127.0.0.1:8000
```

---

# Future Development

* Dashboard Statistics
* Activity Log
* Audit Trail
* User Profile
* Change Password
* Email Verification
* Permission Management
* Role Management
* Pagination
* Search & Filter
* DataTables
* SweetAlert2
* REST API
* JWT Authentication
* Unit Testing
* Docker Support

---

# License

Internal Project.
