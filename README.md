# Rumble + Binom Report System (CSV/JSON Upload)

## Overview
Modern, API-free performance marketing reporting. This Laravel 12 app ingests CSV/JSON exports (no 3rd‑party API keys), normalizes multiple ad network datasets, and renders fast, reliable admin dashboards with spreadsheet‑friendly tables.

Why it stands out:
- Business impact: turns raw exports into actionable daily/weekly/monthly insights in minutes.
- Strong UX: Filament Admin, collapsible grouped views, and one‑click “COPY TABLE” to Google Sheets/Excel (with formulas).
- Pragmatic engineering: resilient parsers, strict date‑range joins, and alphabetic sorting for consistent operations.

## Features
- **Filament Admin Panel** (free)
- **Google Data (CSV)**
  - Parses Account name, Campaign, Cost
  - Weekly/Monthly reports only (date presets end at yesterday); optional one‑line date range auto‑detect
  - Grouped by date range + type with per‑group delete; sorted A→Z by Account, then A→Z by Campaign
- **Rumble Data (CSV)**
  - Parses Campaign, Spend, CPM
  - Date presets and report type (daily/weekly/monthly)
  - Grouped by date range with expand/collapse; campaigns sorted A→Z
- **Binom Rumble Spent Data (CSV)**
  - Parses Name, Leads, Revenue
  - Semicolon-delimited with quoted values (";")
  - Skips rows where Revenue <= 0 (e.g. "0.00 $")
  - Normalizes headers (handles quoted headers and UTF-8 BOM)
  - Date presets and report type (daily/weekly/monthly)
  - Grouped by date range with per-range delete; names sorted A→Z; summary revenue
- **Rumble Campaign Data (JSON)**
  - Parses Name, CPM, Used/Daily Limit (extracts only the limit value; Unlimited → null)
  - Robust header normalization (works with scraped headers)
  - Name cleaning (removes numeric ID prefixes)
  - Grouped by date range with type badges; names sorted A→Z; Daily Limit rendered with $ in grouped view
- **Combined Report: Rumble - Binom Report**
  - Merges `Rumble Data`, `Rumble Campaign Data`, and `Binom Rumble Spent Data`
  - Groups and joins strictly by `date_from`, `date_to`, and `report_type` across all datasets
  - Campaign identity resolution:
    - First tries ID patterns: `NNNNNN_NN` (e.g., `250731_04`), fallback `NNNNNN`
    - Falls back to sanitized name match (trim, collapse spaces, strip trailing parentheses)
    - If still not found, tries base-name match (ID-stripped). Example: trims `- 250730_07 - MR` to compare `Tactical Windshield Tool - US - Angle1`.
    - Last-resort: substring contains match on base names (either direction) to handle Rumble text truncation. Ensures Daily Cap and Set CPM populate when IDs are missing in Campaign Data.
  - Columns (10): Account, Campaign, Daily Cap, Spend, Revenue, P/L, ROI, Conversions, CPM, Set CPM
  - Within each date range, rows are grouped by Account with a per-account summary and an overall SUMMARY row
  - Alphabetically sorted A→Z by Account, and rows A→Z by Campaign
  - Per-section COPY TABLE button to copy the entire table (header, body, footer) as TSV for Google Sheets/Excel
  - COPY TABLE:
    - Copies both TSV (with formulas) and HTML (with formatting) to the clipboard
    - HTML paste preserves formatting: bold Account Summary/SUMMARY rows, italic label cell, and conditional backgrounds for P/L/ROI (positive `#a3da9d`, negative `#ff8080`)
    - SUMMARY row is explicitly included by placing the summary row inside `<tbody>` in the clipboard HTML (on-screen DOM still uses `<tfoot>`)
    - Formulas are preserved in both TSV and HTML for P/L, ROI, Account Summary (Spend/Revenue), and SUMMARY (Spend/Revenue)
    - Injects formulas for P/L and ROI columns (data rows, Account Summary rows, and SUMMARY row) for Google Sheets/Excel paste
    - Dynamic formulas for Account Summary Spend/Revenue (sums over each account's data rows) and for the bottom SUMMARY row (sums all Account Summaries), supporting any number of accounts
  - UI formatting:
    - Account Summary rows and the bottom SUMMARY row are bold.
    - P/L and ROI cells have conditional backgrounds: positive `#a3da9d`, negative `#ff8080` (zero/empty = default background).
    - Implemented via inline styles (not Tailwind bg classes) to satisfy IDE lints.
    - Clipboard HTML also bold+italicizes the "Account Summary" and "SUMMARY" labels in column B.
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
- Google Data: upload CSV (Account name, Campaign, Cost); Weekly/Monthly only; presets end at yesterday; optional one‑line date range auto‑detect (e.g., "28 July 2025 - 3 August 2025")
- Rumble Data: upload CSV (Campaign, Spend, CPM)
- Binom Rumble Spent Data: upload CSV with columns `Name;Leads;Revenue` (quoted, semicolon-delimited)
  - Revenue like `170.00 $` is stored as `170.00`; rows with `0.00 $` are ignored
- Rumble Campaign Data: upload JSON with `header` + `body` arrays
  - Required columns: Name, CPM, Used / Daily Limit
  - Example Daily Limit formats: "$7.19 / $100", "$1,411.21 / Unlimited"
- Choose date preset or custom range and report type
- Use grouped views to review entries by date range
- Manage data using delete actions (All, by Upload Date, by Date Category)

### Navigation (Filament)
- All reporting pages live under the navigation group: `Rumble and Binom Reports Only`.
- Order and labels:
  1. Rumble Data
  2. Rumble Campaign Data
  3. Binom Rumble Spent Data
  4. Rumble Binom Report
- Additional group: `Google and Binom Reports Only`
  1. Google Data

### Combined Report (Rumble - Binom Report)
- Navigate: `Rumble and Binom Reports Only` → `4. Rumble Binom Report`
- Switch report type using the tabs at the top-right of the page content: Daily | Weekly | Monthly. The page lists all batches (date ranges) for the selected type.
- Collapsible sections per exact date range + report type:
  - Section header shows `Date From — Date To`, a type badge, plus total `Spent` and total `Revenue`.
  - Inside each section, a single aligned table shows rows A→Z by Account, then A→Z by Campaign.
  - For each account, an "Account Summary" row appears, followed by a spacer row for readability.
  - A grand SUMMARY row is shown at the bottom (`tfoot`).
- Money formatting: Spend, Revenue, P/L, Daily Cap, CPM, and Set CPM display with a `$` sign.
 - If Binom has revenue for a campaign and there is no matching Rumble spend, the row is still included (revenue-only) so totals remain accurate.
 - Copy table: Click the red COPY TABLE button beside the revenue text in each section header to copy the full table to the clipboard (TSV). Paste directly into Google Sheets/Excel. Copied table preserves formulas for `P/L` and `ROI` columns on data rows, Account Summary rows, and the bottom SUMMARY row. Formula details: `P/L = E{row}-D{row}`, `ROI = (E{row}/D{row})-1`. Formulas are only injected when the destination cell is not empty.

## Import Formats
- Google Data (CSV): `Account name`, `Campaign`, `Cost`
- Rumble Data (CSV): `Campaign`, `Spend`, `CPM`
- Binom Rumble Spent Data (CSV): `Name`, `Leads`, `Revenue` (semicolon `;` delimited, quoted)
- Rumble Campaign Data (JSON): `header` + `body` arrays with columns `Name`, `CPM`, `Used / Daily Limit`

## Export
- (Planned) Export filtered/sorted report as CSV or Excel
  - Include formulas in exports:
    - P/L: `=Revenue - Spend`
    - ROI: `=IF(Spend>0, Revenue/Spend - 1, "")`

## License
MIT
