## [0.7.0] - 2025-08-29
### Added
- Render.com deployment configuration
  - `render.yaml` for Render service configuration
  - `deploy.sh` build script for production deployment
  - Enhanced `.htaccess` with security headers, compression, and caching rules
  - Updated `vite.config.js` for production asset handling
  - Added `.renderignore` to exclude unnecessary files from deployment
  - Updated `README.md` with Render deployment instructions

### Changed
- Optimized asset handling for production environment
- Improved security headers and caching configuration
- Updated dependencies for production build

## [0.6.6] - 2025-08-28
### Added
- Google Binom Report: green "CREATE DRAFT" button creates a Gmail draft for the selected date range (weekly/monthly).
- Email body built on the frontend and sent as full HTML:
  - Greeting/closing included ("Hello Jesse," / "Thanks, Allan").
  - Intro links the date phrase to the newly created Google Sheet when available and shows the period in `dd.mm.YYYY - dd.mm.YYYY` format.
  - Table includes only per-account Account Summary rows plus bottom SUMMARY (no formulas) with conditional backgrounds (green `#a3da9d`, red `#ff8080`) and gray `#dadada` header.
  - Summary table now includes 1px borders with padding and border-collapse for better Gmail rendering.
- Frontend helpers: `GB_buildSheetValues`, `GB_createSheetSilently`, `GB_extractSummaryHtml`, `GB_createDraft`.
- Backend endpoint: `POST /google/gmail/google-binom/create-draft` with controller method `createGoogleBinomGmailDraft`.
  - Subject: "Weekly Report dd.mm.YYYY - dd.mm.YYYY" (or Monthly accordingly).
  - Honors `is_full_body` to use provided HTML body as-is.

### Docs
- README: Documented Google Binom Create Draft behavior, date format, borders/padding, and the new route.

### UI
- Moved "CREATE DRAFT" to the right of "CREATE SHEET" on the Google Binom page toolbar.

## [0.6.5] - 2025-08-28
### Changed
- Rumble Binom Report: "CREATE DRAFT" email body now mirrors the approved layout from the frontend (no formulas; visible borders and padding; conditional green/red backgrounds for P/L and ROI; greeting and closing text included).
- Intro text varies by cadence (Daily/Weekly/Monthly) and links the "yesterday/period" phrase to the created Google Sheet when available.

### Added
- Backend `createRumbleGmailDraft` honors a new `is_full_body` flag to treat the provided `html` as the complete email body, preventing duplicate preface/footer.

### Docs
- README: Documented Rumble Binom "Create Gmail Draft" behavior, link to Google Sheet, styling, and required `gmail.compose` scope.

## [0.6.4] - 2025-08-28
### Added
- Invoice tool: "Create Gmail Draft" action uses Gmail API to create a draft with the generated invoice PDF attached (instead of sending locally).
- Invoice tool: "Connect Google" header action appears when OAuth is required and links to `/google/auth`.

### Changed
- Default Google OAuth scopes now include `https://www.googleapis.com/auth/gmail.compose` in `config/services.php`.

### Docs
- README: Documented Gmail Drafts usage, authorization flow, and updated sample `GOOGLE_SCOPES` to include `gmail.compose`.
- `.env.example`: Provided a sensible `GOOGLE_SCOPES` default including `gmail.compose`.

## [0.6.3] - 2025-08-25
### Added
- Filament "Invoice" tool under Tools:
  - Form with Name, Bill To, Date (today, disabled), Invoice # (auto `YYYY-NNN`), Notes.
  - Repeater for line items (quantity × rate → amount) with auto-calculated total.
  - Header action "Download PDF" saves the invoice and downloads the PDF.

### Fixed
- Invoice PDF now renders with embedded Arial across environments.
  - DomPDF `defaultFont` set to `arialembedded`; Blade uses `@font-face` with absolute file URLs and bold mapped to `arialbd.ttf`.
- Downloaded PDF filename now exactly matches `Allan - {invoice_number}.pdf`.

### Docs
- README: Added Windows setup commands to copy `arial.ttf` and `arialbd.ttf` into `public/fonts/`, plus cache/filename notes.

## [0.6.2] - 2025-08-24
### Added
- Google Binom Sheets export: added a second tab `Summary` alongside `Report`.
  - Columns: Account Name, Total Spend, Revenue, P/L, ROI, ROI Last Week/Month (header mirrors Report text, including `(Full)`/`(Cohort)`).
  - Values are formulas referencing the corresponding `Report` rows (per-account Account Summary and bottom SUMMARY).
  - Formats: gray `#dadada` header; currency on B/C/D; percent on E/F; conditional green/red on P/L (D), ROI (E), ROI Last (F); auto-resize columns A..F.
  - Styling: the label cell "SUMMARY" in column A is bold + italic.

### Docs
- README: documented the new `Summary` sheet behavior and formatting for Google Binom Create Sheet.

## [0.6.1] - 2025-08-24
### Added
- Google Sheets export on Google Binom Report: blue "CREATE SHEET" button builds a formatted Google Sheet mirroring the on-screen 8-column table.
  - Spreadsheet: renames first tab to `Report`, inserts a bold Date row (row 1), gray `#dadada` header (row 2), data from row 3.
  - Formulas: P/L (`=D-C`) in column E and ROI (`=IF(C>0,(D/C)-1, "")`) in column F on data rows; dynamic `SUM` for Account Summary and SUMMARY (Spend/Revenue).
  - Formats: currency on C/D/E; percent on F/G; integer on H; conditional green/red for P/L (E), ROI (F), and ROI Last (G); auto-resize columns A..H.
  - Drive placement: moves file under cadence-specific parent with optional `YYYY` subfolder, controlled by env.
  - Endpoint: `POST /google/sheets/google-binom/create` (auth required).

### Changed
- OAuth popup flow reused for Google Binom export with 120s timeout guard; frontend opens a pending tab immediately to avoid popup blockers and navigates it to the created sheet URL.

### Docs
- README: documented Google Binom Create Sheet behavior, formatting, OAuth flow, Drive placement, and the new route.

## [0.6.0] - 2025-08-18
### Added
- Google Sheets export on Rumble Binom Report: blue "CREATE SHEET" button builds a formatted Google Sheet mirroring the on-screen table.
  - Spreadsheet: renames first tab to `Report`, inserts a bold Date row (row 1), gray `#dadada` header (row 2), data from row 3.
  - Formulas: P/L (`=E-D`) and ROI (`=IF(D>0,(E/D)-1, "")`) on data rows; dynamic `SUM` for Account Summary and SUMMARY.
  - Formats: currency on C/D/E/I/J, percent on G; conditional green/red for P/L and ROI; auto-resize columns A..J.
  - Drive placement: moves file under a cadence-specific parent with optional `YYYY` subfolder, controlled by env.
- Google OAuth flow and endpoints for Sheets/Drive access.
  - `GET /google/auth`, `GET /google/callback`, `POST /google/sheets/rumble/create`.
  - Token stored at `storage/app/private/google_oauth_token.json`.

### Changed
- OAuth popup race condition fixed: listener is attached BEFORE navigating the popup to the auth URL; adds 120s timeout fallback and origin check.
- Backend create endpoint returns `401` with `authorizeUrl` when token is missing/expired; frontend seamlessly resumes after consent.

### Docs
- README updated with Google OAuth + Sheets setup (.env keys, routes) and Create Sheet behavior/formatting.

## [0.5.13] - 2025-08-16
### Changed
- Google Binom Report: COPY TABLE now shows the full date range in the top Date row for Weekly/Monthly, formatted as `DD/MM - DD/MM`.
- Rumble Binom Report: COPY TABLE now shows `DD/MM - DD/MM` for Weekly/Monthly and keeps a single `DD/MM` for Daily.
- Both reports: COPY TABLE buttons pass the correctly formatted date string; formulas remain unchanged and correct (date row stays as row 1).
- Google Binom Report: COPY SUMMARY clipboard HTML header now uses `#dadada` background to match COPY TABLE header styling.

### Docs
- README: Documented Date row behavior for Daily vs Weekly/Monthly and noted that clipboard HTML preserves the header background color (`#dadada`).
 - README: Noted that COPY SUMMARY clipboard HTML header uses `#dadada`.

## [0.5.12] - 2025-08-16
### Added
- Rumble Binom Report: COPY TABLE now includes a top Date row in `DD/MM` format as row 1 (bold, no background). Header becomes row 2; data starts at row 3.

### Changed
- Rumble Binom Report: Table header uses `#dadada` background on-screen and is preserved in the copied HTML so spreadsheet pastes keep the header styling.
- COPY TABLE formulas adjusted to respect the new row offset while preserving P/L, ROI, Account Summary, and SUMMARY computations.
- COPY TABLE button passes `date_to` as `DD/MM` into the copy function.

### Docs
- README: Documented header background styling and Date row behavior (row order, no background) and that clipboard HTML preserves the header background color.

## [0.5.11] - 2025-08-11
### Changed
- Google Binom Report: Account Summary and bottom SUMMARY “ROI Last Week/Month” now use full previous-period totals per account and overall (all campaigns from the previous period), not just the cohort of campaigns listed this week.
  - Added mode selector to switch between Full Totals and Cohort for these summaries; computations now respect the selected mode.

### Added
- Google Binom Report: "COPY SUMMARY" button beside "COPY TABLE".
  - Copies only per-account Account Summary rows and the bottom SUMMARY row.
  - Columns: Account Name, Total Spend, Revenue, P/L, ROI, ROI Last Week/Month.
  - Clipboard: TSV + HTML; no formulas; optimized for Google Sheets, Excel, and email paste (Gmail).
 - Google Binom Report: ROI Last mode toggle in page header (Full Totals vs Cohort). Default is Full. The ROI Last column header shows the active mode in parentheses for clarity.
 - Google Binom Report: Tooltips on ROI Last mode buttons (Full/Cohort) explaining behavior for future reference.

### Docs
- README: Mentioned COPY SUMMARY in Overview and detailed behavior under Google Binom Report.
 - README: Documented that on-screen ROI Last Week/Month applies the same green/red conditional backgrounds as ROI.
 - README: Documented the ROI Last mode toggle (Full vs Cohort) and that row-level ROI Last remains per-campaign.
 - README: Clarified that both COPY TABLE and COPY SUMMARY reflect the selected mode, and added per-button tooltip notes.

### Fixed
- Google Binom Report: ROI LAST WEEK/MONTH could show inflated values due to permissive previous-period matching (name/base/substring fallbacks).
  - Now mirrors current-period logic: if Google campaign has an ID, match by ID only (no fallback). If no ID, allow exact sanitized-name match only. This prevents cross-campaign mismatches.
 - Google Binom Report: COPY SUMMARY did not include ROI Last Week/Month values.
   - Added computation of previous-period ROI for Account Summary and overall SUMMARY; values are rendered on-screen and included in COPY SUMMARY output (with green/red backgrounds in HTML clipboard).

## [0.5.10] - 2025-08-11
### Added
- Google Binom Report page (`App/Filament/Pages/GoogleBinomReport.php`, view `resources/views/filament/pages/google-binom-report.blade.php`):
  - Merges Google Data and Binom Google Spent Data for Weekly/Monthly.
  - Columns: Account, Campaign, Total Spend, Revenue, P/L, ROI, ROI LAST WEEK/MONTH, Sales.
  - Per-section COPY TABLE (TSV + HTML) with formulas for P/L and ROI; Account Summary and SUMMARY totals computed via formulas.
  - Displays Binom-only rows (revenue > 0 or leads > 0) when no Google match exists to keep revenue totals accurate.

### Changed
- Matching rules for Google↔Binom are strict and one-to-one:
  - If a Google row has an ID, it matches Binom only by the same ID (no name/base/substring fallback).
  - If a Google row has no ID, it matches only by exact sanitized name. Base/substring fallbacks are disabled.
  - Prevents cross-campaign merges and revenue inflation; leftover Binom rows are shown as Binom-only entries.
- Grouping prefers Google account name for matched rows so all Google campaigns appear under the expected account group.
- ROI LAST WEEK/MONTH is read directly from prior-period tables (no recursive report rebuild), improving performance.
- Google Data grouped list loads only the latest 12 date groups for speed.
 - COPY TABLE clipboard ROI now pastes as percent string via `TEXT()` formula for both reports.

### Docs
- README: Added Google Binom Report feature, matching rules, grouping behavior, ROI last-period source, and navigation entry.

## [0.5.9] - 2025-08-10
### Added
- Binom Google Spent Data feature (weekly/monthly only):
  - Model `BinomGoogleSpentData`, migration `binom_google_spent_data` table
  - Filament resource + grouped list page (`/admin/binom-google-spent-datas`)
  - CSV upload (semicolon `;` delimited, quoted), parses `Name`, `Leads`, `Revenue`
  - Skips rows with `Revenue <= 0`; header normalization handles quoted headers and UTF-8 BOM
  - Groups strictly by `date_from|date_to|report_type`; rows sorted A→Z by Name
  - Data management: Delete All, Delete by Upload Date, Delete by Date Category
  - Date presets end at yesterday; report types restricted to Weekly/Monthly
  - Info modal in header with screenshot at `public/images/google-binom-info.jpg` (served via `asset('images/google-binom-info.jpg')`)

### Docs
- README: Added Binom Google Spent Data feature details, usage instructions, navigation group entry, and import format

## [0.5.6] - 2025-08-09
### Added
- Google Data feature (weekly/monthly only):
  - Model `GoogleData`, migration `google_data` table
  - Filament resource + grouped list page (`/admin/google-datas`)
  - CSV upload (Account name, Campaign, Cost), optional one-line date range auto-detect
  - Groups by `date_from|date_to|report_type`; rows sorted A→Z by Account, then A→Z by Campaign
- Per-group delete + global delete actions for Google Data (All, by Upload Date, by Date Category)

### Changed
- Navigation: Google Data appears under Filament group `Google and Binom Reports Only` as "1. Google Data"

### Docs
- README: Portfolio-ready Overview copy focused on business impact, UX, and pragmatic engineering
- README: Added Google Data feature details, usage instructions, navigation group, and import format

## [0.5.7] - 2025-08-10
### Fixed
- Rumble Binom Report: Daily Cap and Set CPM sometimes empty when campaign ID missing in `Rumble Campaign Data` due to text truncation.
  - Added fallback matching after ID and sanitized-name: base-name (ID-stripped) exact match, then substring contains match (either direction).
  - New helper `baseName()` trims ID tokens like `250730_07` and short suffixes (e.g., `- MR`) so campaigns such as `Tactical Windshield Tool - US - Angle1 - 250730_07 - MR` match `Tactical Windshield Tool - US - Angle1`.
  - Applied to both Rumble rows and Binom-only rows, so Daily Cap/Set CPM populate whenever present in Campaign Data.
### Docs
- README: Documented base-name and substring fallback strategy in the Combined Report section.

## [0.5.8] - 2025-08-10
### Added
- Binom Rumble Spent Data page: Info icon in header showing a modal with the screenshot of required Binom CSV export settings.
  - Blade partial: `resources/views/filament/resources/binom-rumble-spent-data-resource/partials/binom-export-info.blade.php`
  - Image placed at `public/images/rumble-binom-info.jpg` and referenced via `asset('images/rumble-binom-info.jpg')`.
### Docs
- README: Mentioned Info icon, modal, and image asset location for future reference.

## [0.5.5] - 2025-08-09
### Added
- COPY TABLE now writes both TSV (formulas) and HTML (formatting) to the clipboard.
- HTML paste keeps formatting: bold Account Summary/SUMMARY rows, italic label cell, and conditional backgrounds for P/L/ROI.
- SUMMARY row is guaranteed to paste by including it inside `<tbody>` in the clipboard HTML (onscreen table still uses `<tfoot>`).
- Label cells (column B) for "Account Summary" and "SUMMARY" are bold+italic in clipboard HTML.
### Fixed
- Ensured rich copy fallback behavior (copy event) sets both text/html and text/plain when ClipboardItem isn't available.

# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- [ ] Add report export functionality (CSV/XLSX) with P/L and ROI formulas


## [0.5.4] - 2025-08-09
### Added
- Rumble Binom Report: Added per-section red "COPY TABLE" button beside revenue. Copies the entire table (header, rows, footer) to clipboard as TSV. Works with Google Sheets/Excel paste. Implemented via Alpine.js with secure Clipboard API and fallback.
- COPY TABLE now injects formulas for `P/L` and `ROI` on all data rows, Account Summary rows, and the bottom SUMMARY row, so pasted sheets compute automatically.
  - Formula details: `P/L = E{row}-D{row}`, `ROI = (E{row}/D{row})-1`. Formulas are only inserted for non-empty cells to avoid stray errors.
  - Account Summary rows compute Spend/Revenue as dynamic `SUM` across each account's data rows; the bottom SUMMARY sums all Account Summary Spend/Revenue cells for the final totals. Supports many accounts.
 - UI: Account Summary and the bottom SUMMARY rows are bold; P/L and ROI cells have conditional backgrounds (positive `#a3da9d`, negative `#ff8080`). Implemented via inline styles (not Tailwind bg classes) to satisfy IDE lints.

## [0.5.3] - 2025-08-08
### Changed
- Grouped list pages now sort rows alphabetically (A→Z) within each date range group:
  - `Rumble Data`: by Campaign
  - `Rumble Campaign Data`: by Name
  - `Binom Rumble Spent Data`: by Name
- Navigation consolidation: all four pages/resources appear under the Filament group `Rumble and Binom Reports Only` with corrected labels and explicit order:
  1. Rumble Data
  2. Rumble Campaign Data
  3. Binom Rumble Spent Data
  4. Rumble Binom Report

### Fixed
- Corrected minor pluralization typos in navigation labels.

## [0.5.2] - 2025-08-08
### Changed
- Combined report now groups and joins strictly by `date_from`, `date_to`, and `report_type` across all datasets (Rumble Data, Rumble Campaign Data, Binom Rumble Spent Data). Removed any filtering by `created_at` to avoid timestamp drift issues.
- Section headers now display the date range and report type badge, and show both total Spent and total Revenue.
- Replaced header Filter button with inline report-type tabs (Daily | Weekly | Monthly) for faster switching.
 - Report now includes Binom-only revenue rows (when revenue > 0) even if there is no matching Rumble spend row, ensuring accurate revenue totals.

### Fixed
- Accurate Rumble presence detection for a batch (`has_rumble`) now based on raw Rumble rows for the selected date range and report type.
 - Table renders whenever there are any rows (including Binom-only), not only when Rumble data exists.

### Docs
- README updated to reflect date-range based grouping on the combined report, header totals (Spent + Revenue), and navigation under "Rumble and Binom Reports Only".
  - Also documents the new report-type tabs (rendered at the top-right of the page body) and removal of Filter button.

## [0.5.1] - 2025-08-08
### Changed
- Combined report layout refactored to a single, aligned table (removed collapsible groups)
- Per-account "Account Summary" row now shown immediately below campaigns
- Added spacer row after each account for readability
- Bottom grand totals row renamed to "SUMMARY" and moved to `<tfoot>`
- Alphabetical sorting preserved: A→Z by Account, then A→Z by Campaign

### Fixed
- Consistent money formatting across report:
  - `Daily Cap`, `CPM`, and `Set CPM` now display with `$` using the page formatter
  - Spend, Revenue, and P/L remain formatted with `$`

## [0.5.0] - 2025-08-08
### Added
- Combined page: **Rumble - Binom Report** (`App/Filament/Pages/RumbleBinomReport.php`)
  - Joins `rumble_data`, `rumble_campaign_data`, and `binom_rumble_spent_data` by `date_from`/`date_to` and `report_type`
  - Campaign matching by ID patterns (`NNNNNN_NN` fallback `NNNNNN`) with sanitized name fallback
  - 10 columns: Account, Campaign, Daily Cap, Spend, Revenue, P/L, ROI, Conversions, CPM, Set CPM
  - Header Filter action with report type and date presets that end at yesterday
  - Group totals and overall totals

### Changed
- Alphabetical sorting A→Z
  - Groups sorted by Account
  - Rows within each group sorted by Campaign

### Fixed
- Carbon parse usage in page class
- Blade wrapped with `<x-filament-panels::page>` and Actions components for consistent UI

## [0.4.0] - 2025-08-08
### Added
- Binom Rumble Spent Data CSV upload and grouped view
  - Parses `Name`, `Leads`, `Revenue` from semicolon-delimited, quoted CSV
  - Skips rows with `Revenue <= 0`
  - Date presets and `report_type` (daily/weekly/monthly)
  - Grouped by date range with per-group delete
- Data management actions for Binom data
  - Delete All, Delete by Upload Date, Delete by Date Category

### Changed
- README updated with Binom Rumble Spent Data usage and format details

### Fixed
- CSV header normalization in Binom import (handles quoted headers and UTF-8 BOM)
- Blade view uses fully-qualified `Str::plural()`

## [0.3.0] - 2025-08-08
### Added
- Rumble Campaign Data JSON upload (replacing CSV)
  - Parses `header` + `body` arrays
  - Extracts `Name`, `CPM`, and only the numeric Daily Limit (handles Unlimited → null)
- Data management actions in grouped views
  - Delete All
  - Delete by Upload Date (DATE(created_at))
  - Delete by Date Category (`report_type`: daily/weekly/monthly)

### Changed
- Grouped list for Rumble Campaign Data now shows Daily Limit with `$` in the table UI
- README updated to reflect Laravel 12, JSON upload, and new delete actions

### Fixed
- Blade: fully-qualified `Str::plural()` in grouped list to avoid undefined class
- More robust CPM and Daily Limit parsing from JSON (handles currency symbols and extra text)

## [0.2.0] - 2025-08-07
### Added
- Date-based grouping for Rumble Data
  - Reports grouped by upload date with expandable sections
  - Summary information for each date (total spend, campaign count)
  - Clean, modern UI with dark mode support
- Report type support
  - Added daily/weekly/monthly report types
  - Color-coded report type badges
  - Date range presets based on report type
- Enhanced data management
  - Improved table layout and readability
  - Better mobile responsiveness
  - Quick view of key metrics for each date group

## [0.1.0] - 2025-08-06
### Added
- Initial Laravel 12 project setup with Filament Admin
- Rumble CSV upload functionality
  - File upload with validation
  - Automatic campaign name cleaning (removes ID prefixes)
  - Date range selection with presets (yesterday, last 7 days, last month)
  - Data storage for Campaign, Spend, and CPM
- Data management
  - Sortable and searchable data tables
  - Filter by date ranges
  - Clean, spreadsheet-like interface
- User management
  - Admin panel access
  - No user restrictions (all uploads are visible to all admins)
