# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- [ ] Add report export functionality (CSV/XLSX) with P/L and ROI formulas

## [0.5.2] - 2025-08-08
### Changed
- Combined report now groups and joins strictly by `date_from`, `date_to`, and `report_type` across all datasets (Rumble Data, Rumble Campaign Data, Binom Rumble Spent Data). Removed any filtering by `created_at` to avoid timestamp drift issues.
- Section headers now display the date range and report type badge, and show both total Spent and total Revenue.
- Replaced header Filter button with inline report-type tabs (Daily | Weekly | Monthly) for faster switching.

### Fixed
- Accurate Rumble presence detection for a batch (`has_rumble`) now based on raw Rumble rows for the selected date range and report type.

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
