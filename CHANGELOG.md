# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- [ ] Add Binom CSV import functionality
- [ ] Implement data merging between Rumble and Binom reports
- [ ] Add report export functionality

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
