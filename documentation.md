## **📄 Project Documentation**

**System Name:** Google Ads + Binom Report System — File Upload Version
**Framework:** Laravel 11 (PHP 8.2+)
**Database:** MySQL / MariaDB
**Front-end:** Laravel Blade + TailwindCSS (or Bootstrap)
**Authentication:** Laravel Breeze or Jetstream (optional if only admin users)

---

### **1. Overview**

The **Google Ads + Binom Report System** lets users upload CSV files from **Google Ads** and **Binom**.
The system automatically:

* Parses and validates the uploaded CSVs.
* Merges data based on a shared key (Campaign Name or Campaign ID).
* Calculates key performance metrics in a unified table.
* Exports merged results to **CSV** or **Excel**.

---

### **2. User Roles**

| Role                | Description                                                |
| ------------------- | ---------------------------------------------------------- |
| **Admin**           | Can upload files, view reports, export data, manage users. |
| **User** (optional) | Can upload and view reports but not manage users.          |

---

### **3. Core Features**

#### **3.1 File Upload**

* Upload **Google Ads CSV**
* Upload **Binom CSV**
* Multiple file validation:

  * Must be `.csv` format
  * Maximum size (configurable, e.g., 10MB)
  * Required columns:

    * **Google Ads CSV:** Campaign Name/ID, Clicks, Impressions, Conversions, Cost
    * **Binom CSV:** Campaign Name/ID, Clicks, Conversions, Revenue, ROI

#### **3.2 Data Processing**

* Parse CSV into arrays/collections.
* Normalize campaign names (trim, lowercase, remove special chars if needed).
* Match campaigns using:

  1. **Exact match by ID** (preferred)
  2. **Fallback: exact name match**
* Merge data into a single dataset.

#### **3.3 Calculations**

* Merge metrics:

  * **Clicks** (Google Ads & Binom for comparison)
  * **Conversions** (Google Ads & Binom for comparison)
  * **CTR** = (Clicks / Impressions) × 100 (from Google Ads)
  * **CPC** = Cost / Clicks
  * **Revenue** = from Binom
  * **Profit** = Revenue − Cost
  * **ROI** = (Profit / Cost) × 100
* Flag mismatches between Google Ads and Binom conversions.

#### **3.4 Report Display**

* Paginated, searchable table.
* Filters:

  * Date range (if in CSV)
  * Campaign name search
  * ROI filter (positive/negative)
* Sorting by any column.

#### **3.5 Export**

* Export to:

  * **CSV**
  * **Excel (XLSX)** via Laravel Excel
* Include filter/sort state in export.

---

### **4. Database Structure**

#### **4.1 Tables**

**users** (if authentication)

```
id, name, email, password, role, timestamps
```

**uploads**

```
id, user_id, google_ads_file, binom_file, uploaded_at
```

**campaign\_reports**

```
id, upload_id, campaign_id, campaign_name,
clicks_google, clicks_binom,
impressions_google,
conversions_google, conversions_binom,
cost_google, revenue_binom,
profit, roi, created_at, updated_at
```

---

### **5. Laravel Package Dependencies**

* `maatwebsite/excel` → For CSV/XLSX import/export
* `laravel/breeze` → Simple auth scaffolding
* `tailwindcss` → Styling
* `spatie/laravel-permission` (optional) → Role management

---

## **🛠 Development Plan**

### **Phase 1 — Setup**

1. Install Laravel 11

   ```bash
   composer create-project laravel/laravel google-ads-binom-report
   ```
2. Setup `.env` for MySQL.
3. Install Breeze for authentication (optional).

   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install
   npm install && npm run dev
   ```

---

### **Phase 2 — Models & Migrations**

* Create models: `Upload`, `CampaignReport`.
* Add migrations with columns above.
* Run `php artisan migrate`.

---

### **Phase 3 — File Upload Logic**

* Create a form for Google Ads CSV and Binom CSV.
* Validate file type and size.
* Store uploaded files in `/storage/app/uploads/`.

---

### **Phase 4 — CSV Parsing & Merging**

* Use `maatwebsite/excel` to parse CSVs.
* Map required fields.
* Merge by `campaign_id` or `campaign_name`.
* Calculate metrics.

---

### **Phase 5 — Save & Display Reports**

* Save merged data into `campaign_reports`.
* Create a Blade view to show reports in a paginated table.
* Add filters and search.

---

### **Phase 6 — Export Feature**

* Add “Export CSV” and “Export Excel” buttons.
* Use `maatwebsite/excel` for exporting.

---

### **Phase 7 — Polish & Test**

* Error handling for missing/invalid columns.
* Display mismatch warnings.
* Optimize large file handling (chunk reading).

