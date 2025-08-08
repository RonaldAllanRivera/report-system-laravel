# Rumble + Binom Report System (CSV/JSON Upload)

## Overview
This Laravel 12 application lets you upload reports from Rumble and Binom, processes them, and shows grouped, filterable admin views. No API needed—upload CSV/JSON and get insights.

## Features
- **Filament Admin Panel** (free)
- **Rumble Data (CSV)**
  - Parses Campaign, Spend, CPM
  - Date presets and report type (daily/weekly/monthly)
  - Grouped by upload date with expand/collapse
- **Binom Rumble Spent Data (CSV)**
  - Parses Name, Leads, Revenue
  - Semicolon-delimited with quoted values (";")
  - Skips rows where Revenue <= 0 (e.g. "0.00 $")
  - Normalizes headers (handles quoted headers and UTF-8 BOM)
  - Date presets and report type (daily/weekly/monthly)
  - Grouped view with per-range delete and summary revenue
- **Rumble Campaign Data (JSON)**
  - Parses Name, CPM, Used/Daily Limit (extracts only the limit value; Unlimited → null)
  - Robust header normalization (works with scraped headers)
  - Name cleaning (removes numeric ID prefixes)
  - Grouped view with type badges; Daily Limit rendered with $ in grouped view
- **Data Management**
  - Delete All
  - Delete by Upload Date (per day)
  - Delete by Date Category (daily/weekly/monthly)
- **UX**
  - Clean HTML tables for grouped views (dark mode supported)
  - Color-coded badges for report types

## Technologies
- Laravel 12 (PHP 8.3+)
- Filament Admin (free version)
- MySQL / MariaDB
- Laravel Breeze (optional, for authentication)
- TailwindCSS (or Bootstrap)
- [maatwebsite/excel](https://laravel-excel.com/) for CSV/XLSX import/export
- [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) (optional)

## Installation
1. **Clone the repo:**
   ```bash
   git clone <repo-url>
   cd report-system-laravel
   ```
2. **Install dependencies:**
   ```bash
   composer install
   npm install && npm run dev
   ```
3. **Configure environment:**
   - Copy `.env.example` to `.env` and set DB credentials
   - Set file upload size in `php.ini` if needed
4. **Run migrations:**
   ```bash
   php artisan migrate
   ```
5. **(Optional) Install Breeze for authentication:**
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install
   npm install && npm run dev
   ```
6. **(Optional) Install Spatie Permission:**
   ```bash
   composer require spatie/laravel-permission
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

7. **Install Filament Admin (free):**
   ```bash
   composer require filament/filament:"^3.0"
   php artisan filament:install
   php artisan migrate
   ```

## Usage
- Log in to Filament Admin
- Rumble Data: upload CSV (Campaign, Spend, CPM)
- Binom Rumble Spent Data: upload CSV with columns `Name;Leads;Revenue` (quoted, semicolon-delimited)
  - Revenue like `170.00 $` is stored as `170.00`; rows with `0.00 $` are ignored
- Rumble Campaign Data: upload JSON with `header` + `body` arrays
  - Required columns: Name, CPM, Used / Daily Limit
  - Example Daily Limit formats: "$7.19 / $100", "$1,411.21 / Unlimited"
- Choose date preset or custom range and report type
- Use grouped views to review entries by upload date
- Manage data using delete actions (All, by Upload Date, by Date Category)

## Import Formats
- Rumble Data (CSV): `Campaign`, `Spend`, `CPM`
- Binom Rumble Spent Data (CSV): `Name`, `Leads`, `Revenue` (semicolon `;` delimited, quoted)
- Rumble Campaign Data (JSON): `header` + `body` arrays with columns `Name`, `CPM`, `Used / Daily Limit`

## Export
- (Planned) Export filtered/sorted report as CSV or Excel

## License
MIT
