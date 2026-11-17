<p align="center"><img width="160" src=".github/assets/logo.svg"></p>

![Gymie](.github/assets/banner.png)

## Overview

**Gymie** is a modern, full-featured web application for gym, fitness club, and personal training management. Built with **Laravel 13**, **Filament 5**, and **Livewire 4**, it provides robust role-based access, client portals, personal training workout builders, membership lifecycles, invoicing, and real-time operational dashboards.

For more information, visit [https://www.gymie.in](https://www.gymie.in).

---

## Key Features

### 1. Multi-Role Separation & Dedicated Portals
* **Super Admin**: Comprehensive dashboard with gym-wide financial metrics, cashflow trends, subscription health, staff management, and system configuration.
* **Front Desk**: Front-of-house operational control covering member registration, enquiry & lead capture, subscription renewals, invoices, and payments.
* **Personal Trainer**: Dedicated coaching hub featuring active client rosters, customized workout program authoring, today's schedule with 1-click completion/absent tracking, and capacity utilization metrics.
* **Member Portal**: Isolated client portal allowing gym members to inspect active subscription days, view assigned coach contact details, access tailored workout routines, and track upcoming appointments.
* **Dynamic Navigation**: Unauthorized navigation groups and resources are completely invisible to unauthorized roles (zero 403 Forbidden dead-ends).
* **Member Account Linking**: Staff can provision or revoke member portal logins directly from the member profile with automated role assignment.

### 2. Workout Programs & Personal Training Architecture
* **Master Exercise Library**: Encyclopedia of gym movements categorized by muscle groups (Chest, Back, Legs, Core, etc.) and equipment types (Barbell, Dumbbell, Cable, Machine, Bodyweight) with video demonstration URLs and coaching cues.
* **Custom Workout Programs**: Program authoring with custom split day labels (e.g. *Day 1: Upper Push*), target sets, reps, intensity (RPE), rest timers, and movement notes.
* **1-on-1 Session Scheduling**: Scheduling service that enforces active subscription validation, prevents trainer double-booking, and handles cancellations.
* **Automatic Personal Record (PR) Tracker**: Evaluates logged weights in real time against member historical bests and automatically flags new Personal Records.

### 3. Membership & Financial Management
* **Flexible Plans & Services**: Tiered gym memberships, personal training packages, and ad-hoc fitness services.
* **Invoicing & Transaction Tracking**: Automatic invoice generation, payment receipts, partial payments, and PDF invoice downloads (`barryvdh/laravel-dompdf`).
* **Subscription Lifecycle**: Automated background commands to monitor and flag expiring or renewed memberships.
* **Expense Management**: Categorized gym expenditure tracking and reporting.

### 4. Real-Time Operational Dashboards & Performance
* **Smart Background Polling (30s)**: Operational widgets (*Today's Coaching Schedule*, *Expiring Memberships*, *Recent Transactions*) refresh automatically in the background without browser reloads.
* **Database Protection**: Heavy multi-month financial charts are decoupled from polling to eliminate redundant aggregations.
* **Client-Side Form Preloading**: Static form fields (Country, Region, City, Lead Owner, Source, Goal) are preloaded into Alpine.js memory, eliminating network latency and keystroke-level AJAX requests.

### 5. Localization & Regional Customization
* **Default Currency**: Built-in support for **ETB (Ethiopian Birr)** as the primary currency, with automatic price formatting across all invoices and forms.
* **Regional State & City Preloading**: Preloaded Ethiopian Regional States and Chartered Cities (Addis Ababa, Dire Dawa, Oromia, Amhara, Sidama, Tigray, etc.) with sub-cities, alongside an on-the-fly custom city creator for unlisted towns.
* **Phone Auto-Formatting**: Automatic `+251` dialing code formatting and placeholder detection.
* **In-Memory Caching**: Permanent caching (`Cache::rememberForever`) for geographic and currency lookups.

---

## Role Matrix Overview

| Role | Navigation & Scope | Key Dashboard Capabilities |
| :--- | :--- | :--- |
| **`super_admin`** | Full access to all modules, financial records, users, and system settings. | Cashflow Trends, Expense Breakdown, Top Plans, Member Metrics, Financial KPIs. |
| **`front_desk`** | Members, Subscriptions, Invoices, Payments, Enquiries, and Follow-ups. | Expiring Soon Subscriptions, Membership Overview, Quick Member Registration. |
| **`trainer`** | Assigned Clients, Workout Programs, Personal Training Sessions, and Exercise Library. | Today's Coaching Schedule (with 1-click status updates), Active Clients, Capacity Utilization. |
| **`member`** | Read-only access strictly isolated to their own Subscriptions, Invoices, Workouts, and Sessions. | Member Portal Overview (Plan validity, Coach info, Program summary, Upcoming sessions). |

---

## Requirements

- **PHP**: `>= 8.4`
- **Laravel Framework**: `^13.0`
- **Filament Admin Panel**: `5.x`
- **Livewire**: `4.x`
- **MySQL**: `>= 8.0`
- **Composer**: `^2.x`
- **Node.js & npm**: `^20.x` or higher

---

## Installation & Setup

### 1. Clone the Repository
```bash
git clone git@github.com:lubusIN/gymie.git
cd gymie
```

### 2. Install Dependencies
```bash
composer install
npm install && npm run build
```

### 3. Prepare Environment
```bash
composer run prepare-env
```
This command automatically:
* Copies `.env.example` to `.env` (if not present).
* Generates the application key (`APP_KEY`).
* Clears configuration cache.
* Creates the storage symbolic link (`php artisan storage:link`).

### 4. Configure Database Credentials
Edit `.env` with your database connection details:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gymie
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Initialize the Database

Choose one of two setup options:

#### Option A: Blank Setup (Production)
```bash
composer run setup
```
> [!NOTE]
> This runs fresh migrations, seeds reference data (Shield permissions, country/state dataset), and prompts you to create a Super Admin user via the terminal.

#### Option B: Demo Dataset Setup (Local Testing)
```bash
composer run setup-demo
```
> [!CAUTION]
> This command resets the database and seeds realistic demo data (200+ members, subscriptions, workout programs, invoices, expenses, and role-assigned users). Use this only in development.

**Default Demo Credentials:**
```text
Email: admin@gymie.local
Password: password
```

---

## Development Workflow

### 1. Run All Services Concurrently
```bash
composer run dev
```
Starts `php artisan serve`, `php artisan queue:listen`, and `npm run dev` simultaneously via Concurrently.

Or start services individually:

* **Web Server:**
  ```bash
  php artisan serve
  ```
  Access the panel at: [http://127.0.0.1:8000](http://127.0.0.1:8000)

* **Queue Worker (Background emails & exports):**
  ```bash
  php artisan queue:work
  ```

* **Task Scheduler (Subscription status updates):**
  ```bash
  php artisan schedule:work
  ```

---

## Testing

Gymie has a comprehensive test suite covering unit logic, feature tests, role-based data scoping, widget visibility, and API endpoints:

```bash
# Run all tests via Pest
composer test

# Or run Pest directly
php vendor/pestphp/pest/bin/pest
```

---

## API Documentation (JSON v1)

Gymie includes a versioned RESTful API under `/api/v1` authenticated using Laravel Sanctum bearer tokens.

### Authentication
* **Login**: `POST /api/v1/auth/login`
* **Current User**: `GET /api/v1/me`
* **Logout**: `POST /api/v1/auth/logout`

Example:
```bash
# 1. Obtain Bearer Token
curl -sX POST "http://127.0.0.1:8000/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gymie.local","password":"password"}'

# 2. Authenticated Request
curl -s "http://127.0.0.1:8000/api/v1/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

### Rich Filtering & Parameters
All API index endpoints support allowlisted query parameters:
* **Search**: `?q=search_term`
* **Pagination**: `?page=1&per_page=15`
* **Sorting**: `?sort=-created_at,name` (prefix `-` for descending)
* **Relations**: `?include=service,subscription.member`
* **Filtering**: `?filter[field]=value` or date ranges `?filter[date]=2026-01-01..2026-12-31`
* **Soft Deletes**: `?trashed=with` or `?trashed=only`

---

## Troubleshooting & Optimizations

* **Memory Limits**: Ensure PHP has adequate memory allocated in your `php.ini` (`memory_limit = 512M`).
* **Cache Management**:
  ```bash
  # Clear all application, route, and view caches
  php artisan optimize:clear

  # Pre-cache Filament components and Blade icons for production speed
  php artisan filament:optimize
  ```
* **Permission Cache Reset**:
  ```bash
  php artisan permission:cache-reset
  ```

---

## Meet Your Artisans

[LUBUS](https://lubus.in/?utm_source=github&utm_medium=open-source&utm_campaign=laravel-gymie-v3) is a web design agency based in Mumbai.

<a href="https://cal.com/lubus">
<img src="https://raw.githubusercontent.com/lubusIN/.github/refs/heads/main/profile/banner.png" />
</a>

---

## License

Gymie is open-sourced software licensed under the [MIT license](LICENSE).
