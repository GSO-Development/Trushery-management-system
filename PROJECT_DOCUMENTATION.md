# George Steuart Group Treasury Management Portal — Complete Technical Documentation & Architecture

## 1. System Overview & Architecture

The **George Steuart Group Treasury Management Portal** is an enterprise-grade multi-tenant web application built with **Laravel 11, Livewire (Volt), Alpine.js, TailwindCSS, and MySQL**. 

It is designed to manage the treasury operations, liquidity, loans, fixed deposits, and bank accounts for the parent company (**George Steuart & Co**) and all **10 Subsidiary Companies (Entities)**.

```
                  ┌─────────────────────────────────────────┐
                  │          George Steuart Group           │
                  │       Treasury Portal Gateway           │
                  └────────────────────┬────────────────────┘
                                       │
            ┌──────────────────────────┼──────────────────────────┐
            ▼                          ▼                          ▼
 ┌─────────────────────┐    ┌─────────────────────┐    ┌─────────────────────┐
 │  Super Admin Panel  │    │    CEO Dashboard    │    │ Sub-Company Portals │
 │     (/admin/*)      │    │   (/ceo/dashboard)  │    │  (/{company_slug}/*)│
 └─────────────────────┘    └─────────────────────┘    └─────────────────────┘
```

---

## 2. Authentication & Authorization Flow

The application supports dual authentication mechanisms protected by specialized custom middleware:

```
[ User Visit ] ──► /login ──┬──► Local Credentials (Email + Password) ──┐
                            └──► Microsoft Azure AD SSO (/auth/microsoft) ┴──► [ Authenticated Session ]
                                                                                       │
                                          ┌────────────────────────────────────────────┴────────────────────────────────────────────┐
                                          ▼                                            ▼                                            ▼
                                   is_admin == true                            is_ceo == true                              Sub-Company User
                                          │                                            │                                            │
                                          ▼                                            ▼                                            ▼
                               Redirect /admin/dashboard                    Redirect /ceo/dashboard                 Redirect /{company_slug}/summary-dashboard
                               (Protected: admin.access)                   (Protected: ceo.access)                  (Protected: tenant.access)
```

### Authentication Types:
1. **Local Authentication:** Standard email/password login handled via Livewire Volt (`/login`).
2. **Microsoft Azure AD SSO:** Enterprise OAuth 2.0 Single Sign-On handled via Socialite/Microsoft Graph API (`/auth/microsoft` -> `/auth/microsoft/callback`).

### Access Control Middleware:
- **`AdminAccess` (`admin.access`):** Restricts `/admin/*` routes strictly to users with `is_admin == true`.
- **`CeoAccess` (`ceo.access`):** Restricts `/ceo/*` routes strictly to users with `is_ceo == true`.
- **`TenantAccess` (`tenant.access`):** Protects sub-company routes (`/{company_slug}/*`). Validates:
  1. User is authenticated.
  2. User belongs to the requested company (`user->company_id === company->id`).
  3. User's assigned `Group` has `nav_permissions` enabled for the requested route key.

---

## 3. Sub-Company Entities (All 10 Companies)

The system maintains 10 separate subsidiary view folders and tenant routes under `resources/views/livewire/tenant/{company_slug}/`:

| # | Entity Legal Name | Slug (`company_slug`) | Treasury Login Email | Assigned Group |
|---|-------------------|------------------------|----------------------|----------------|
| 1 | George Steuart Health (Pvt) Ltd | `health` | `treasury@health.gs.com` | Treasury |
| 2 | George Steuart Teas (Pvt) Ltd | `optimize` | `treasury@teas.gs.com` | Treasury |
| 3 | George Steuart Travels Ltd | `travels` | `treasury@travels.gs.com` | Treasury |
| 4 | George Steuart Solutions (Pvt) Ltd | `solutions` | `treasury@solutions.gs.com` | Treasury |
| 5 | George Steuart Insurance Brokers (Pvt) Ltd | `gsib` | `treasury@gsib.gs.com` | Treasury |
| 6 | Waskaduwa Beach Resort PLC | `waskaduwa` | `treasury@waskaduwa.gs.com` | Treasury |
| 7 | Hikkaduwa Beach Resort PLC | `hikkaduwa` | `treasury@hikkaduwa.gs.com` | Treasury |
| 8 | Citrus Silver Ltd | `citrus_silver` | `treasury@citrus.silver.gs.com` | Treasury |
| 9 | Citrus Leisure PLC | `citrus_leisure` | `treasury@citrus.leisure.gs.com` | Treasury |
| 10 | Citrus LT (Pvt) Ltd | `citrus_lt` | `treasury@citrus.lt.gs.com` | Treasury |

*Default Password for all test accounts:* `Treasury@1234`
*Super Admin Login:* `admin@gs.com` / `Admin@1234`
*CEO Login:* `ceo@gs.com` / `Ceo@1234`

---

## 4. Complete Directory & Folder Structure

```
d:\new_GS\gs_long_report\
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LogoutController.php
│   │   │   │   └── MicrosoftAuthController.php
│   │   │   ├── Ceo/
│   │   │   │   └── CeoDashboardController.php
│   │   │   └── Tenant/
│   │   │       ├── CashPositionController.php   # Handles Cash Position module & 4 sub-sections
│   │   │       ├── FixedDepositController.php    # Handles 3.3 Fixed Deposits
│   │   │       ├── LongTermLoanController.php    # Handles 3.1 Long Term Loans
│   │   │       ├── TenantController.php          # Handles Summary Dashboard & Rate Mgmt
│   │   │       └── WorkingCapitalController.php  # Handles 3.2 Working Capital
│   │   └── Middleware/
│   │       ├── AdminAccess.php                   # Protects /admin/*
│   │       ├── CeoAccess.php                     # Protects /ceo/*
│   │       └── TenantAccess.php                  # Protects /{company_slug}/*
│   └── Models/
│       ├── Bank.php                              # Master Sri Lanka banks table
│       ├── BankEntry.php                         # Daily bank rate & available amounts
│       ├── CashMovementEntry.php                 # Cash flow breakdown (Collections, Payments...)
│       ├── CashPositionEntry.php                 # Daily bank account balances (Opening, In, Out, Closing)
│       ├── Company.php                           # Sub-companies model
│       ├── CompanyBankAccount.php                # Account numbers per entity seeded from Admin
│       ├── FixedDeposit.php                      # 3.3 Fixed deposits portfolio
│       ├── Group.php                             # User groups & nav_permissions JSON
│       ├── LongTermLoan.php                      # 3.1 Term loans portfolio
│       ├── User.php                              # System users (Admin, CEO, Treasury)
│       └── WorkingCapitalLoan.php                # 3.2 Short term facilities portfolio
│
├── database/
│   ├── migrations/
│   │   ├── 2026_07_23_000001_create_treasury_tables.php
│   │   ├── 2026_07_27_043042_create_company_bank_accounts_table.php
│   │   └── 2026_07-27_100000_create_cash_position_tables.php
│   └── seeders/
│       ├── CompanyBankAccountSeeder.php          # Seeds 100+ account numbers from Excel
│       └── DatabaseSeeder.php                    # Seeds all entities, banks, users, sample data
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── admin.blade.php                   # Admin layout (collapsible icon sidebar)
│   │   │   ├── ceo.blade.php                     # CEO dashboard layout
│   │   │   └── portal.blade.php                  # Sub-company portal layout
│   │   └── livewire/
│   │       ├── admin/                            # Super Admin Livewire Volt views
│   │       │   ├── banks/index.blade.php         # Bank master & company account numbers panel
│   │       │   ├── companies/index.blade.php     # Company master management
│   │       │   ├── groups/index.blade.php        # Group permission management
│   │       │   └── users/index.blade.php         # User management
│   │       └── tenant/                           # Sub-Company views per slug
│   │           ├── health/                       # Primary master views template
│   │           │   ├── summary_dashboard.blade.php
│   │           │   ├── cash_position.blade.php
│   │           │   ├── long_term_loans.blade.php
│   │           │   ├── working_capital.blade.php
│   │           │   └── fixed_deposits.blade.php
│   │           ├── optimize/                     # George Steuart Teas views
│   │           ├── travels/                      # George Steuart Travels views
│   │           ├── solutions/                    # George Steuart Solutions views
│   │           ├── gsib/                         # GS Insurance Brokers views
│   │           ├── waskaduwa/                    # Waskaduwa Beach Resort views
│   │           ├── hikkaduwa/                    # Hikkaduwa Beach Resort views
│   │           ├── citrus_silver/                # Citrus Silver views
│   │           ├── citrus_leisure/               # Citrus Leisure views
│   │           └── citrus_lt/                    # Citrus LT views
│
└── routes/
    ├── web.php                                   # Main web, auth, admin & CEO routes
    └── tenant.php                                # Sub-company tenant routes /{company_slug}/*
```

---

## 5. Sub-Company Pages & Feature Specification

Every sub-company portal contains **9 core modules** accessible via the collapsible side navbar:

```
Sub-Company Sidebar Navigation
│
├── 📊 1. Summary Dashboard       (/{company_slug}/summary-dashboard)
├── 💰 2. Cash Position            (/{company_slug}/cash-position)
├── 🏛️ 3. Long Term Loans          (/{company_slug}/long-term-loans)
├── 💵 4. Working Capital          (/{company_slug}/working-capital)
├── 🏦 5. Fixed Deposits           (/{company_slug}/fixed-deposits)
├── 🗓️ 6. Repayment Schedule       (/{company_slug}/repayment-schedule)   [NEW]
├── 📜 7. Transaction History      (/{company_slug}/transaction-history)  [NEW]
├── 📈 8. Interest Rate Master     (/{company_slug}/rate-master)          [NEW]
└── 🛡️ 9. Audit Logs               (/{company_slug}/audit-logs)           [NEW]
```

---

### Module 6: 🗓️ Repayment Schedule (`/{company_slug}/repayment-schedule`)
- Track upcoming principal & interest installments, due dates, paid statuses, and overdue alerts.
- Inline schedule item creation via `+ Add Schedule Item`.
- Action button to mark pending installments as `Paid`.

### Module 7: 📜 Transaction History (`/{company_slug}/transaction-history`)
- Detailed ledger for all facility drawdowns, repayments, interest payments, and fee transactions with reference numbers.

### Module 8: 📈 Interest Rate Master (`/{company_slug}/rate-master`)
- Manage base rates (AWPLR, SLFR, SOFR), bank margins, and effective interest rates per assigned bank over time (`Effective Rate = Base Rate + Margin`).

### Module 9: 🛡️ Audit Logs (`/{company_slug}/audit-logs`)
- Searchable security audit trail tracking user actions (`CREATE`, `UPDATE`, `DELETE`, `LOGIN`), timestamps, modules, descriptions, and IP addresses.

---

### Module 1: 📊 Summary Dashboard (`/{company_slug}/summary-dashboard`)
An executive overview featuring **8 real-time KPI cards** and **interactive Chart.js graphs**:
1. **Total Cash:** Available liquid cash + Fixed Deposits total.
2. **Available Cash:** Liquid bank account balances.
3. **Restricted / Pledged Cash:** Fixed deposits pledged against bank facilities.
4. **Total Debt Outstanding:** Long Term Loans + Working Capital Loans total debt.
5. **Available Credit Facilities:** Approved bank credit limit total.
6. **Fixed Deposits:** Total investment portfolio.
7. **13-Week Cash Forecast:** Estimated net cash position projection.
8. **Upcoming Debt / Cash Payments:** Maturing FDs & overdue payments count/amount.
- **Visual Graphs:**
  - *Cash & Asset Distribution* (Doughnut Chart: Available Cash, Pledged Cash, FDs).
  - *Credit Limits vs Debt Outstanding* (Bar Chart: Approved Limits vs Debt Outstanding).

---

### Module 2: 💰 Cash Position (`/{company_slug}/cash-position`)
A comprehensive daily cash and liquidity management module organized into **4 interactive tabs**:

#### Tab 1: 💰 Cash Summary
- High-level cards for Total Cash, Available Cash, Restricted Cash, Active Bank Accounts.
- Daily Cash In / Cash Out summary block.

#### Tab 2: 🏦 Bank Accounts Table
- Automatically lists **only bank accounts assigned to that company by Admin** (`CompanyBankAccount`).
- Columns: **Date, Bank, Account Number, Opening Balance, Cash In, Cash Out, Restricted Cash, Closing Balance, Remarks, Action**.
- Inline editing: Users enter daily Opening Balance, Cash In, Cash Out, and Remarks.
- **Dynamic Formula:** `Closing Balance = Opening Balance + Cash In - Cash Out`.

#### Tab 3: 💸 Cash Movement Breakdown
- Daily cash inflow and outflow tracking:
  - **Customer Collections (+)**
  - **Loan Drawdowns (+)**
  - **Supplier Payments (-)**
  - **Salaries (-)**
  - **Taxes (-)**
  - **Loan Repayments (-)**
  - **Other Payments (-)**

#### Tab 4: 📊 Liquidity Summary
- Liquidity metrics: *Available Cash, Available Overdraft (OD), Available Working Capital, Total Liquidity* (`Available Cash + Available OD + Available WC`).

---

### Module 3: 🏛️ Long Term Loans (`/{company_slug}/long-term-loans`)
- Section 3.1 Term Loan Portfolio management.
- **Table Columns:** `Bank, Loan Type, Tenor (year/month), Facility Amt, Granted Date, Rate %, Rem. Tenor, Outstanding, CCY, Entry Date, Del`.
- **`+ Add Row`** button triggers an inline table row for seamless data entry.

---

### Module 4: 💵 Working Capital (`/{company_slug}/working-capital`)
- Section 3.2 Short Term Facilities (PCL, Overdrafts, Money Market Loans, Import LC).
- **Table Columns:** `Bank, Facility Type, Tenor (year/month), Facility Amt, Obtained, Settlement, Overdue, Rate %, Outstanding, CCY, Entry Date, Del`.
- **Auto-Overdue Calculation:** Calculates overdue days automatically if current date > settlement date.
- **`+ Add Row`** button for inline table insertion.

---

### Module 5: 🏦 Fixed Deposits (`/{company_slug}/fixed-deposits`)
- Section 3.3 Fixed Deposit & Investment portfolio.
- **Table Columns:** `Bank / Institute, Amount, CCY, Commenced, Maturity, Tenor (year/month), Rate %, Status, Renewal, Pledged, Entry Date, Del`.
- **Auto Tenor:** Automatically calculates tenor string (e.g. `1 Year`, `6 Months`) from commencement and maturity dates.
- Status highlights active, maturing soon (<= 30 days), and already matured deposits.

---

## 6. Super Admin Panel (`/admin/*`)

Accessible by `admin@gs.com`:
- **/admin/companies:** Create & configure companies, assign bank relationships.
- **/admin/banks:** Manage Sri Lanka bank master records. Click **Accounts** to view and review all company bank account numbers seeded from the Daily Cash Position Report.
- **/admin/groups:** Manage user group roles and set `nav_permissions` (`summary_dashboard`, `cash_position`, `long_term_loans`, `working_capital`, `fixed_deposits`).
- **/admin/users:** Manage system users, assign roles and company affiliations.

---

## 7. Group CEO Dashboard (`/ceo/dashboard`)

Accessible by `ceo@gs.com`:
- Consolidated executive dashboard aggregating metrics across **all 10 subsidiary companies**.
- Group-wide Total Debt, Total Cash, Total Fixed Deposits, and Company-by-Company comparative analysis.
