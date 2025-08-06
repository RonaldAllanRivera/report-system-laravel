# Rumble + Binom Report System (CSV Upload Version)

## Overview
This Laravel 12 application allows users to upload CSV files from Rumble and Binom, automatically processes the data, and provides performance reports. No direct API integration is required—just upload your CSVs and get actionable insights.

## Features
- **Filament Admin Panel** (free version)
- **Rumble CSV Upload**
  - Upload daily, weekly, or monthly reports
  - Automatic campaign name cleaning (removes ID prefixes)
  - Date range selection with presets (yesterday, last 7 days, last month)
- **Data Processing**
  - Parse and store Campaign, Spend, and CPM data
  - Support for multiple report types (daily, weekly, monthly)
- **Data Visualization**
  - Sortable and searchable data tables
  - Filter by date ranges
  - Clean, spreadsheet-like interface
- **User Management**
  - All admins have full access to all data
  - No user restrictions (all uploads are visible to all admins)

## Technologies
- Laravel 11 (PHP 8.2+)
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
- Log in as Admin (if auth enabled)
- Upload Google Ads and Binom CSV files via the dashboard
- View, filter, and export unified reports

## CSV Format Requirements
### Google Ads CSV
- Required columns: `Campaign Name/ID`, `Clicks`, `Impressions`, `Conversions`, `Cost`

### Binom CSV
- Required columns: `Campaign Name/ID`, `Clicks`, `Conversions`, `Revenue`, `ROI`

## Export
- Export filtered/sorted report as CSV or Excel

## License
MIT
