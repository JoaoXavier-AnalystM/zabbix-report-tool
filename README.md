# Zabbix Report Tool — PDF, Excel, SLA & Maintenance Manager

A PHP web application that installs alongside your existing Zabbix server and adds a modern reporting and maintenance interface. No modifications to Zabbix required.

**Compatible with Zabbix 6.0 · 6.4 · 7.0 · 7.4**

---

## ✨ Features

### 📄 PDF Report Generator
- 5-step wizard: select hosts → templates → items → time range → generate
- Inline graph preview with 6 chart types (line, area, bar, spline, step, scatter)
- Full progress tracking during generation

### 📊 Excel Export — 4 report types
| Report | Description |
|--------|-------------|
| General Host List | All monitored hosts with status |
| Detailed Host Inventory | OS, RAM, CPU min/avg/peak, memory, disks, uptime |
| Problem Report | Alerts and events for a selected period |
| Peaks Report | CPU and memory peak values per host |

### 📈 SLA Compliance Report
- ICMP-based availability calculated from **trigger events** (not just ping checks)
- Shows real SLA%, total downtime, and every incident with start/end/duration
- Export to HTML view or CSV
- Multi-version compatible (6.0 through 7.4)

### 🔧 Maintenance Manager
- List all maintenances with live status (Active / Scheduled / Expired)
- Create maintenances with full schedule support:
  - One-time, Daily, Weekly, Monthly
  - Monthly supports **Day of month** and **Day of week** modes (identical to Zabbix UI)
- Add hosts to existing maintenances
- Export host lists per maintenance to CSV
- Host autocomplete search

### 🖥️ Latest Data Explorer
- Browse and filter all monitored items across hosts and groups
- Real-time autocomplete for hosts and groups
- Paginated table with inline filtering
- One-click export to PDF

---

## 🎨 Interface

- Modern dark/light theme with persistent preference
- Custom background image support
- Fully responsive
- Bilingual: **English / Portuguese (Brazil)**
- Sticky topbar with glassmorphism cards

---

## 🔒 Security

- Session-based authentication via Zabbix API
- CSRF protection on all forms
- No data stored outside your Zabbix database

---

## 📚 Installation

### Requirements
- PHP 7.2 or higher
- PHP extensions: curl, gd, json, mbstring, xml, zip, zlib, fileinfo
- Composer (for dompdf dependency)
- Write permissions on `tmp/` and `logs/` directories

### Quick Setup

1. Copy the project folder to a directory accessible by your web server (e.g., `/var/www/html/zabbix-report/`)

2. Create `config.php` from the example:
   ```bash
   cp config.php.example config.php
   ```

3. Edit `config.php` and set your Zabbix URL and API credentials:
   ```php
   define('ZABBIX_URL', 'http://your-zabbix-server/zabbix');
   define('ZABBIX_API_USER', 'api_user');
   define('ZABBIX_API_PASS', 'api_password');
   ```

4. Install Composer dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. Access `login.php` from your browser.

### Optional: Add to Zabbix menu

Edit `/usr/share/zabbix/include/classes/helpers/CMenuHelper.php`, find:
```php
$submenu_reports = array_filter($submenu_reports);
```
Add above it:
```php
$submenu_reports[] = CWebUser::checkAccess(CRoleHelper::UI_REPORTS_SYSTEM_INFO)
    ? (new CMenuItem(_('PDF Report')))
          ->setUrl(new CUrl('zabbix-report/login.php'), true)
          ->setId('report_pdf')
          ->setAliases(['zabbix-report/chooser.php'])
    : null;
```

### LDAP / Active Directory

Set prefix/suffix in `config.php` if your Zabbix uses LDAP:
```php
define('ZBX_USER_PREFIX', 'DOMAIN\\');   // Active Directory
define('ZBX_USER_SUFFIX', '@domain.local');  // Email UPN
```

---

## 🌐 Language Support

English and Portuguese (Brazil) included. Add new languages by creating `lang/XX.php` and adding the code to `SUPPORTED_LANGS` in `lib/i18n.php`.
