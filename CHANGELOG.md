# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- [ ] Implement data merging between Rumble and Binom reports
- [ ] Add report export functionality

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
