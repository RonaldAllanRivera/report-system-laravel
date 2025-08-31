# Multi-Source Marketing Data Dashboard (CSV/JSON)

## Overview
Modern reporting with API‑free data ingestion and optional Google APIs for export and email. This Laravel 12 app ingests CSV/JSON exports (no 3rd‑party API keys) for all datasets, then uses Google OAuth for one‑click “CREATE SHEET” (Sheets/Drive) and “CREATE DRAFT” (Gmail) actions. It normalizes multiple ad network datasets and renders fast, reliable admin dashboards with spreadsheet‑friendly tables and email‑ready account summaries. Built as a production‑grade automation project with clean UX, strong data correctness guarantees, and a clear automation roadmap.

Why it stands out:
- Business impact: turns raw exports into actionable daily/weekly/monthly insights in minutes.
- Strong UX: Filament Admin, collapsible grouped views, and one‑click “COPY TABLE” and “COPY SUMMARY” for Google Sheets/Excel/email (formulas for tables; clean values for summaries).
- Pragmatic engineering: resilient parsers, strict date‑range joins, and alphabetic sorting for consistent operations.
 - Data accuracy: strict one‑to‑one campaign matching (ID‑first, sanitized‑name fallback) and Binom‑only revenue rows ensure no double counting and no missing revenue.
 - Performance & scale: fast grouped views, lazy loads, and recent‑period limits on heavy pages while keeping totals consistent.
 - Spreadsheet fidelity: COPY TABLE outputs TSV + rich HTML with formulas (P/L, ROI, summaries) so pasted sheets compute immediately.
 - Optional Google integration: one‑click “CREATE SHEET” uses Google OAuth + Sheets/Drive APIs; data ingestion remains API‑free.
 - Invoices: one‑page Filament tool to generate and download PDF invoices instantly — auto invoice number (YYYY‑NNN), inline line‑item totals, embedded Arial font, and fixed filename `Allan - {invoice_number}.pdf`.

Roadmap (APIs & automation):
- Connect Gmail (OAuth/Gmail API) to auto‑ingest email report attachments.
- Connect Google Ads API to sync Account/Campaign/Cost data on a schedule.
- Connect Rumble REST API to pull Campaign/Spend/CPM (CSV remains as fallback).
- Connect Binom REST API to fetch Name/Leads/Revenue.
- Production‑ready ops: .env‑driven credentials, Laravel Scheduler jobs, idempotent upserts, retries/backoff, rate limiting, and observability.

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
  - Info icon in header opens a modal with a screenshot of the required Binom CSV export settings (for future reference)
    - Image location: `public/images/rumble-binom-info.jpg` (served via `asset('images/rumble-binom-info.jpg')`)
- **Binom Google Spent Data (CSV)**
  - Parses Name, Leads, Revenue
  - Semicolon-delimited with quoted values (";")
  - Skips rows where Revenue <= 0 (e.g. "0.00 $")
  - Weekly/Monthly reports only (no daily); presets end at yesterday
  - Grouped by date range with per-range delete; names sorted A→Z; summary revenue
  - Info icon in header opens a modal with a screenshot of the required Google/Binom CSV export settings
    - Image location: `public/images/google-binom-info.jpg` (served via `asset('images/google-binom-info.jpg')`)
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
    - Adds a top Date row as row 1 (bold, no background). Header is row 2; data starts at row 3. All formulas are adjusted accordingly.
      - Daily: `DD/MM`
      - Weekly/Monthly: `DD/MM - DD/MM`
    - Clipboard HTML preserves the header background color (`#dadada`) so pasted tables maintain header styling.
  - UI formatting:
    - Account Summary rows and the bottom SUMMARY row are bold.
    - P/L and ROI cells have conditional backgrounds: positive `#a3da9d`, negative `#ff8080` (zero/empty = default background).
    - Implemented via inline styles (not Tailwind bg classes) to satisfy IDE lints.
    - Clipboard HTML also bold+italicizes the "Account Summary" and "SUMMARY" labels in column B.
    - Header row uses `#dadada` background on-screen.

- **Google Binom Report**
  - Merges `Google Data` and `Binom Google Spent Data` for Weekly/Monthly reports.
  - Columns: Account, Campaign, Total Spend, Revenue, P/L, ROI, ROI LAST WEEK/MONTH, Sales.
  - Strict one-to-one matching:
    - If a Google campaign has an ID, it can only match a Binom row with the same ID (no name/base/substring fallback).
    - If the Google campaign has no ID, only an exact sanitized-name match is allowed.
    - Any unmatched Binom rows with `revenue > 0` or `leads > 0` are shown as Binom‑only rows to preserve totals.
  - Grouping prefers the Google `account_name` so Google campaigns always appear under their expected account group.
  - ROI LAST WEEK/MONTH is read directly from the prior period’s raw tables (no recursive report rebuild).
    - Account Summary and the bottom SUMMARY “ROI Last Week/Month” use full previous-period totals per account and overall (all campaigns from the previous period), not just this week’s cohort.
    - Row-level “ROI Last Week/Month” remains per-campaign, i.e., shown only when a previous-period match exists for that campaign.
    - A header toggle lets you switch “ROI Last” summary behavior between Full Totals and Cohort. The column header shows the current mode in parentheses (Full/Cohort). Default is Full.
  - Includes the same COPY TABLE behavior as above (TSV+HTML with formulas and formatting).
    - Date row matches the section range: Weekly/Monthly use `DD/MM - DD/MM`.
    - Clipboard HTML preserves the header background color (`#dadada`).
  - Clipboard reflects mode: Both COPY TABLE and COPY SUMMARY copy values as currently rendered, matching the selected ROI Last mode (Full/Cohort) for Account Summary and SUMMARY.
  - Tooltips: Hover the Full/Cohort buttons (page header and section header) for a quick explanation of each mode.
  - COPY SUMMARY:
    - Copies only per‑account Account Summary rows and the bottom SUMMARY row.
    - Columns: Account Name, Total Spend, Revenue, P/L, ROI, ROI Last Week/Month.
    - No formulas; outputs TSV + HTML for clean paste into Google Sheets/Excel and email (Gmail).
    - HTML clipboard applies the same conditional backgrounds as COPY TABLE: green `#a3da9d` for positive and red `#ff8080` for negative in P/L, ROI, and ROI Last Week/Month cells.
    - Header row in the clipboard HTML uses `#dadada` background.
  - UI formatting:
    - On-screen table applies green `#a3da9d` for positive and red `#ff8080` for negative values in P/L, ROI, and ROI Last Week/Month.
- **Data Management**
  - Delete All
  - Delete by Upload Date (per day)
  - Delete by Date Category (daily/weekly/monthly)
- **UX**
  - Clean HTML tables for grouped views (dark mode supported)
  - Color-coded badges for report types

 - **Invoice Tool (Filament Page)**
   - Navigate: Tools → Invoice
   - Form: Name, Bill To, Date (today, disabled), Invoice # (auto `YYYY-NNN`), Notes
   - Line Items: item, quantity, rate; amount and total auto-calculated
   - Action: Download PDF (saves invoice + items, then downloads)
   - Action: Create Gmail Draft (attaches the generated invoice PDF)
   - PDF: embedded Arial; filename `Allan - {invoice_number}.pdf`

## Technologies
- Laravel 12 (PHP 8.3+)
- Filament Admin (free version)
- MySQL / MariaDB
- Laravel Breeze (optional, for authentication)
- TailwindCSS (or Bootstrap)
- [maatwebsite/excel](https://laravel-excel.com/) for CSV/XLSX import/export
- [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) (optional)
- Google APIs PHP Client (`google/apiclient`)

## Deployment

### Render.com (via Blueprint)

This project is configured for zero-touch deployment to Render using a `render.yaml` Blueprint. The Blueprint automatically provisions a Docker container to run the Laravel application.

1.  **Prerequisites**
    *   A GitHub/GitLab/Bitbucket account with your repository.
    *   A Render.com account.
    *   A remote MySQL database (e.g., from Hostinger, Aiven, or Render's own PostgreSQL service).

2.  **Deployment Steps**
    1.  In the Render Dashboard, click **New +** and select **Blueprint**.
    2.  Connect the Git repository for this project.
    3.  Render will automatically detect and parse the `render.yaml` file. It will show a plan to create one "Web Service" using Docker.
    4.  Click **Apply** to approve the plan.

3.  **Environment Variables**

    Before the first deployment, you **must** set the following secret environment variables in the Render dashboard for the service. The deployment will fail without them.

    *   `DB_PASSWORD`: Your database password.
    *   `GOOGLE_CLIENT_ID`: Your Google OAuth client ID.
    *   `GOOGLE_CLIENT_SECRET`: Your Google OAuth client secret.

    You should also generate a secure `APP_KEY` locally (`php artisan key:generate --show`) and set it in the dashboard. Other variables from `.env.example` are pre-filled in `render.yaml` but can be overridden in the dashboard.

4.  **Post-Deployment**

    *   The Docker container's entrypoint script (`docker/entrypoint.sh`) automatically handles key generation (if missing), config caching, and database migrations (`php artisan migrate --force`). No manual post-deployment SSH steps are needed.
    *   Once deployed, update the `APP_URL` and `GOOGLE_REDIRECT_URI` environment variables in the Render dashboard to use your service's `*.onrender.com` URL.

### Production (Render) checklist
- **APP_URL** set to your HTTPS domain, e.g. `https://report-system-laravel.onrender.com` (no trailing slash).
- **ASSET_URL** not set (recommended). If you set it, use the same HTTPS base as `APP_URL`.
- **SESSION_SECURE_COOKIE=true** in production. Keep it `false` locally.
- **HTTPS enforcement**: `URL::forceScheme('https')` is enabled only in production in `app/Providers/AppServiceProvider.php` to avoid mixed-content.
- **Trusted proxies**: configured in `bootstrap/app.php` so `X-Forwarded-Proto`/host are respected on Render.
- **APP_KEY** present and database credentials set. Migrations run automatically on deploy via `docker/entrypoint.sh`.

### Troubleshooting: 403 Forbidden at `/admin`
- Ensure `app/Models/User.php` implements `Filament\Models\Contracts\FilamentUser` and allows access via:
  - `public function canAccessPanel(Filament\Panel $panel): bool { return true; }`
- Redeploy after code changes (config/routes cache is rebuilt on container start).
- Verify a user exists in the production `users` table. Passwords must be bcrypt-hashed. Then log in at `/admin/login`.

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

### Google OAuth + Sheets Setup
- Create a Google Cloud OAuth 2.0 Client (Web application) and set the Authorized redirect URI to your app's callback, e.g.:
  - `http://localhost:8000/google/callback` (dev) or your production domain
- Required `.env` keys (see `config/services.php`):
  - `GOOGLE_CLIENT_ID=...`
  - `GOOGLE_CLIENT_SECRET=...`
  - `GOOGLE_REDIRECT_URI=http://localhost:8000/google/callback`
  - `GOOGLE_SCOPES="https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/gmail.compose"`
  - Optional: `GOOGLE_APP_NAME="Report System"`
- Optional Google Drive folder placement (used by Create Sheet):
  - `GOOGLE_DRIVE_DEFAULT_PARENT_ID=` (fallback parent folder)
  - `GOOGLE_DRIVE_DAILY_PARENT_ID=` (if set, daily sheets land here)
  - `GOOGLE_DRIVE_WEEKLY_PARENT_ID=` (if set, weekly sheets land here)
  - `GOOGLE_DRIVE_MONTHLY_PARENT_ID=` (if set, monthly sheets land here)
  - `GOOGLE_SHEET_SUBFOLDER_PATTERN=YYYY` (auto create/use a year subfolder under the cadence parent)
- Token storage: `storage/app/private/google_oauth_token.json`. Delete this file to force re-consent.
- Routes:
  - `GET /google/auth` → starts OAuth
  - `GET /google/callback` → saves token and notifies opener
  - `POST /google/sheets/rumble/create` (auth required) → creates the Google Sheet
  - `POST /google/sheets/google-binom/create` (auth required) → creates the Google Binom sheet
  - `POST /google/gmail/google-binom/create-draft` (auth required) → creates a Gmail draft for Google Binom
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

### Laragon (Windows) local setup
- __Location__: place the project at `C:\laragon\www\report-system-laravel\`
- __Auto virtual hosts__: Laragon → Preferences → General → enable "Auto virtual hosts" → Save → Reload. Access at `http://report-system-laravel.test`.
- __Local .env__ (copy `.env.example` → `.env`, then adjust minimally):

```ini
APP_ENV=local
APP_DEBUG=true
APP_URL=http://report-system-laravel.test

# Choose ONE database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=report_system_local
DB_USERNAME=root
DB_PASSWORD=

# Or use SQLite (no MySQL setup)
# DB_CONNECTION=sqlite
# DB_DATABASE=database/database.sqlite
# (Create an empty file at database/database.sqlite)

CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=
```

- __First run__ (once): install dependencies and run migrations

```bash
composer install
php artisan migrate
```

- __Daily usage__: Start Laragon (Apache/MySQL) and open `http://report-system-laravel.test/admin/login`.

Notes:
- Local `.env` is gitignored and won’t affect Docker/Render.
- HTTPS is forced only in `production`; local stays on HTTP.

### PDF Invoices (DomPDF) — Windows font setup
 The app embeds Arial for invoice PDFs. On Windows, copy the system fonts into `public/fonts/`:
 
 ```powershell
 New-Item -ItemType Directory -Force -Path .\public\fonts
 Copy-Item "C:\Windows\Fonts\arial.ttf"   ".\public\fonts\arial.ttf"
 Copy-Item "C:\Windows\Fonts\arialbd.ttf" ".\public\fonts\arialbd.ttf"
 ```

 Notes:
 - Filename: downloads as `Allan - {invoice_number}.pdf` (fixed; no timestamps).
 - Font cache: DomPDF stores font metrics in `storage/fonts/`. If font changes don't appear, delete files in that folder; metrics regenerate on next render.
 - Version control: `.gitignore` excludes `storage/fonts/` and `public/fonts/*.ttf` so caches and local font binaries are not committed.

### Gmail Drafts (Invoice tool)
- Purpose: Instead of sending email locally, the Invoice page can create a Gmail draft via the Gmail API.
- Requirements:
  - OAuth consent completed with scopes including `gmail.compose` (see `.env` sample above).
  - Token is stored at `storage/app/private/google_oauth_token.json`.
- Authorize:
  - Visit `/google/auth` and complete consent. If scopes change, delete the token file and re-authorize.
- Usage:
  - Navigate to `Tools → Invoice`.
  - If not authorized, a yellow "Connect Google" action appears; click it to start authorization.
  - When authorized, use the green "Create Gmail Draft" action, fill in To/Subject/Body, and submit. A Gmail draft will be created with the invoice PDF attached (filename `Allan - {invoice_number}.pdf`). Success shows the Draft ID.
  - From email address uses `mail.from.*` in your config; body is plain text.

## Usage
- Log in to Filament Admin
- Google Data: upload CSV (Account name, Campaign, Cost); Weekly/Monthly only; presets end at yesterday; optional one‑line date range auto‑detect (e.g., "28 July 2025 - 3 August 2025")
- Rumble Data: upload CSV (Campaign, Spend, CPM)
- Binom Rumble Spent Data: upload CSV with columns `Name;Leads;Revenue` (quoted, semicolon-delimited)
  - Revenue like `170.00 $` is stored as `170.00`; rows with `0.00 $` are ignored
- Binom Google Spent Data: upload CSV with columns `Name;Leads;Revenue` (quoted, semicolon-delimited)
  - Weekly/Monthly only; date presets end at yesterday; rows with `Revenue <= 0` are ignored
  - Info modal shows required export settings screenshot
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
  2. Binom Google Spent Data
  3. Google Binom Report

 - Additional group: `Tools`
   1. Invoice

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
 - Copy table: Click the red COPY TABLE button beside the revenue text in each section header to copy the full table to the clipboard (TSV). Paste directly into Google Sheets/Excel. Copied table preserves formulas for `P/L` and `ROI` columns on data rows, Account Summary rows, and the bottom SUMMARY row. Formula details: `P/L = E{row}-D{row}`, `ROI = TEXT((E{row}/D{row})-1, "0.00%")`. Formulas are only injected when the destination cell is not empty.

### Create Google Sheet (Rumble - Binom Report)
- Each section has a blue "CREATE SHEET" button that builds a Google Sheet with the same data and formulas.
- Behavior and formatting:
  - Sheet name = `<date range> - Rumble Ads` (e.g., `28/07 - 03/08 - Rumble Ads`)
  - First tab renamed to `Report`
  - Row 1 = Date row (bold). Row 2 = header with gray `#dadada` background. Data starts at row 3.
  - Formulas: P/L (`=E-D`) and ROI (`=IF(D>0,(E/D)-1, "")`) on data rows; dynamic `SUM` for Account Summary and SUMMARY rows
  - Number formats: currency on C/D/E/I/J; percent on G (ROI)
  - Conditional format: green `#a3da9d` when > 0 and red `#ff8080` when < 0 on P/L (F) and ROI (G)
  - Auto-resize columns A..J
  - File is moved to a Drive folder based on cadence parents and optional year subfolder (see env keys above)
- OAuth flow:
  - If not authorized, a popup/tab opens to Google. After consenting, the popup notifies the app and closes; creation resumes automatically.
  - Robust listener ordering avoids race conditions; a 120s timeout fallback prevents indefinite waiting if the popup is closed early.
  - Ensure your browser allows popups for the app domain.

### Create Gmail Draft (Rumble - Binom Report)
- Each section has a green "CREATE DRAFT" button that creates a Gmail draft for the current date range.
- Email body (built on the frontend and sent as full HTML):
  - Greeting: "Hello Jesse," and closing: "Thanks, Allan".
  - Intro: "Here is the Rumble <Cadence> Report from <yesterday/period>" with the phrase linked to the created Google Sheet when available.
  - Table: final computed values only (no formulas); visible borders and padding for readability; conditional background colors for P/L and ROI (green when > 0, red when < 0).
- Backend respects a flag to use the provided full HTML body as-is to avoid duplicate prefaces.
- OAuth flow matches Create Sheet:
  - Requires scope `https://www.googleapis.com/auth/gmail.compose`.
  - If not authorized, a popup prompts consent, then creation resumes automatically; includes a timeout safety.
- Subject and recipients are composed on the backend; adjust defaults in `app/Http/Controllers/GoogleSheetsController.php` if needed.

### Create Google Sheet (Google Binom Report)
 - Each section has a blue "CREATE SHEET" button that builds a Google Sheet mirroring the on-screen Google Binom table.
 - Sheet name = `<date range> - Google Ads` (e.g., `28/07 - 03/08 - Google Ads`)
 - First tab renamed to `Report`
 - Adds a second tab named `Summary`:
   - Columns: `Account Name`, `Total Spend`, `Revenue`, `P/L`, `ROI`, `ROI Last Week/Month` (header mirrors `Report` column text, including `(Full)`/`(Cohort)`).
   - Values are formulas referencing the corresponding `Report` rows: per‑account Account Summary rows and the bottom `SUMMARY` row.
   - Formatting: gray `#dadada` header; currency on B/C/D; percent on E/F; conditional green `#a3da9d` and red `#ff8080` backgrounds for P/L (D), ROI (E), and ROI Last (F).
   - Auto-resize columns A..F; the label cell "SUMMARY" in column A is bold + italic.
 - Row 1 = Date row (bold). Row 2 = header with gray `#dadada` background. Data starts at row 3.
 - Formulas: P/L (`=D-C`) in column E and ROI (`=IF(C>0,(D/C)-1, "")`) in column F on data rows; dynamic `SUM` for Account Summary and SUMMARY rows (Spend/Revenue).
 - Number formats: currency on C/D/E; percent on F/G; integer on H.
 - Conditional format: green `#a3da9d` when > 0 and red `#ff8080` when < 0 on P/L (E), ROI (F), and ROI Last (G).
 - Auto-resize columns A..H.
 - File is moved to a Drive folder based on cadence parents and optional year subfolder (see env keys).
 - OAuth flow matches Rumble: if not authorized, popup prompts consent, then creation resumes automatically; includes a 120s timeout safety.

### Create Gmail Draft (Google Binom Report)
- Each section has a green "CREATE DRAFT" button that creates a Gmail draft for the current date range.
- Email body (built on the frontend and sent as full HTML):
  - Greeting: "Hello Jesse," and closing: "Thanks, Allan".
  - Intro: "Here is the Google <Cadence> Report from <period>" where `<period>` is formatted as `dd.mm.YYYY - dd.mm.YYYY` and linked to the created Google Sheet when available.
  - Table: summary-only (per-account Account Summary rows and bottom SUMMARY); no formulas; conditional backgrounds for P/L, ROI, and ROI Last (green `#a3da9d` when > 0, red `#ff8080` when < 0); header uses `#dadada` background; visible 1px borders (`#bfbfbf`) with padding and `border-collapse` for clean rendering in Gmail.
- Backend respects a flag to use the provided full HTML body as-is (`is_full_body`) to avoid duplicate preface/footer.
- OAuth flow matches Create Sheet and requires scope `https://www.googleapis.com/auth/gmail.compose`.
- Subject is composed automatically as "Weekly Report dd.mm.YYYY - dd.mm.YYYY" (or Monthly accordingly).

 ## Import Formats
- Google Data (CSV): `Account name`, `Campaign`, `Cost`
- Rumble Data (CSV): `Campaign`, `Spend`, `CPM`
- Binom Rumble Spent Data (CSV): `Name`, `Leads`, `Revenue` (semicolon `;` delimited, quoted)
- Binom Google Spent Data (CSV): `Name`, `Leads`, `Revenue` (semicolon `;` delimited, quoted; weekly/monthly only)
- Rumble Campaign Data (JSON): `header` + `body` arrays with columns `Name`, `CPM`, `Used / Daily Limit`

## Export
- (Planned) Export filtered/sorted report as CSV or Excel
  - Include formulas in exports:
    - P/L: `=Revenue - Spend`
    - ROI: `=IF(Spend>0, Revenue/Spend - 1, "")`

## Future Integrations
- Google Email (Gmail API)
  - OAuth 2.0 via Google Cloud; secure token storage and automatic refresh
  - Optional: label-based fetch and webhook/polling to auto-import report attachments

- Google Ads API
  - OAuth 2.0; pull Account name, Campaign, Cost for weekly/monthly
  - Scheduled sync with rate limiting/retries; reconcile against CSV via feature flag

- Rumble REST API
  - Use official endpoints if available; otherwise keep CSV fallback
  - Fetch Campaign, Spend, CPM; map into `rumble_data`

- Binom REST API
  - API key/token auth; fetch Name, Leads, Revenue; map into Binom tables
  - Preserve strict `date_from|date_to|report_type` joins and 1:1 matching rules

- Platform & Ops
  - .env-driven credentials; Laravel Scheduler jobs; idempotent upserts by `(date_from, date_to, report_type, campaign key)`
  - Retries with exponential backoff, rate limiting, metrics/audit logs

## License
MIT
