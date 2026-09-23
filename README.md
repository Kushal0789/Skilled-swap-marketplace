# Skill Swap Marketplace 🚀
> A Modern, Production-Style Peer-to-Peer Knowledge Exchange Web Application

Built from scratch using **PHP 8.x, MySQL, HTML5, CSS3, and Vanilla JavaScript (ES6+)** with a strict **Three-Tier Architecture**. Designed specifically for academic excellence, viva presentations, and standard **Windows 11 + XAMPP** environments.

---

## 🌟 Key Features

- **3-Tier Architecture**: Strict separation between Presentation (Frontend), Application (Business Logic & REST APIs), and Data (MySQL PDO).
- **Skill Matching Engine**: Intelligent Two-Way reciprocal matching ($100\%$ mutual compatibility) and One-Way mentorship recommendations.
- **Dynamic Modern UI/UX**:
  - Dark / Light mode toggle with instant persistence via `localStorage`.
  - CSS Custom Properties design system with curated HSL color tokens.
  - Responsive layout for Mobile, Tablet, Laptop, and 4K Displays.
  - Non-blocking dynamic Toast notifications & animated Modals.
  - Visual swap exchange diagrams.
- **Skill Lifecycle Management**:
  - Full CRUD operations for Offered and Wanted skills with Proficiency ratings (Beginner, Intermediate, Advanced, Expert).
  - Searchable master taxonomy categorized into Programming, Design, Creative, Education, Business, etc.
- **Swap Proposal System**:
  - Send, Accept, Reject, and Cancel swap requests.
  - State machine with active duplicate-prevention constraints.
- **Integrated Private Messaging**:
  - Asynchronous chat client with incremental delta polling (`since_id`).
  - Read receipts, timestamps, and conversation sidebar.
- **Reviews & Ratings**:
  - 1–5 star rating system with written feedback after completed exchanges.
- **Notifications Engine**:
  - Real-time notification bell dropdown for new swap proposals, acceptances, and chat messages.
- **Administrative Portal**:
  - User moderation (activate, suspend, delete).
  - Master skills taxonomy editor.
  - Content and member report resolution.
- **Security Hardened**:
  - PDO Prepared Statements (Complete SQLi immunity).
  - BCRYPT Password Hashing (`password_hash`).
  - CSRF Token verification.
  - XSS output escaping.
  - Secure image upload validation with MIME check and `.htaccess` execution shield.

---

## 🏗️ Technology Stack

| Layer | Technology | Details |
|---|---|---|
| **Presentation** | HTML5, CSS3, Vanilla JS ES6+ | Custom CSS Grid/Flexbox, Fetch API, no frameworks |
| **Application** | Pure PHP 8.x | REST-like JSON endpoints, Session Auth, CSRF Guards |
| **Data Layer** | MySQL 5.7+ / MariaDB 10.4+ | InnoDB, PDO with utf8mb4 charset, Foreign Key constraints |
| **Web Server** | Apache (via XAMPP) | Runs on Windows 11 / Windows 10 |

---

## 📂 Project Directory Structure

```
skill-swap/
│
├── index.php                 # Landing page (Hero, Live Stats, Featured Categories, Demo)
├── login.php                 # User Sign-In with 1-click Demo Fill buttons
├── register.php              # User Registration with client & server validation
├── dashboard.php             # User Control Center (Metrics, Matches, Quick Swaps)
├── profile.php               # Profile Management & User Skills CRUD
├── discover.php              # Live Search & Skill Discovery Portal
├── matches.php               # Two-Way Reciprocal Matching Interface
├── requests.php              # Swap Requests Dashboard (Incoming / Outgoing / History)
├── messages.php              # Private Direct Messaging Chat Client
├── settings.php              # Password Updates & Account Overview
├── admin.php                 # Moderation & Platform Administration Console
├── logout.php                # Secure Session Invalidation & Redirect
│
├── config/
│   └── db.php                # Centralized PDO connection & transaction helpers
│
├── includes/
│   ├── auth.php              # Session checks, role guards, CSRF tokens
│   ├── functions.php         # JSON responses, avatar resolution, time formatting
│   ├── validation.php        # Input validators & secure file upload handlers
│   ├── header.php            # Global responsive header, navbar & theme toggle
│   └── footer.php            # Global footer, toast container & modal backdrop
│
├── api/
│   ├── auth/                 # login.php, register.php, logout.php, check.php
│   ├── users/                # profile.php, update_profile.php, search.php
│   ├── skills/               # list.php, add.php, update.php, delete.php
│   ├── matches/              # find.php (Skill compatibility matching engine)
│   ├── requests/             # send.php, list.php, accept.php, reject.php, cancel.php
│   ├── messages/             # conversations.php, list.php, send.php
│   ├── notifications/        # list.php, read.php
│   ├── reviews/              # add.php, list.php
│   ├── reports/              # create.php
│   └── admin/                # stats.php, users.php, skills.php, reports.php
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Design system tokens, dark/light themes, typography
│   │   ├── components.css    # Badges, cards, modals, toast notifications, buttons
│   │   └── responsive.css    # Breakpoints for Mobile, Tablet, and Desktop
│   ├── js/
│   │   ├── app.js            # Global theme manager, toast, modal & apiFetch wrapper
│   │   ├── auth.js           # Interactive form validation & AJAX login/register
│   │   ├── dashboard.js      # Dashboard widgets & dynamic match previews
│   │   ├── discover.js       # Live debounced search & swap proposal modal
│   │   ├── matches.js        # Two-way match matrix & proposal sender
│   │   ├── requests.js       # Request state transitions & review modal
│   │   ├── messages.js       # Delta polling chat client (3.5s intervals)
│   │   ├── profile.js        # Avatar upload preview & user skills manager
│   │   └── admin.js          # Admin dashboard metrics, table actions & forms
│   ├── images/
│   │   └── default-avatar.svg# Universal fallback avatar SVG
│   └── uploads/
│       └── avatars/          # User uploaded profile pictures (.htaccess secured)
│
├── database/
│   └── skill_swap.sql        # Complete schema, foreign keys, triggers & seed data
│
├── DOCUMENTATION.md          # Comprehensive Viva Examination Guide & Architecture Q&A
└── README.md                 # Setup Guide & Documentation
```

---

## ⚡ Installation & XAMPP Setup (Windows 11)

Follow these simple steps to run the application on your computer:

### Step 1: Install & Start XAMPP
1. Download and install **XAMPP for Windows** (with PHP 8.x) from [apachefriends.org](https://www.apachefriends.org).
2. Open the **XAMPP Control Panel**.
3. Click **Start** for both **Apache** and **MySQL** (both indicators should turn green).

### Step 2: Deploy Project Files
Place this project inside your XAMPP web root directory:
```
C:\xampp\htdocs\skill-swap
```
*(Ensure all folders match the directory structure shown above).*

### Step 3: Import the Database
1. Open your browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab in the top navigation bar.
3. Click **Choose File** and select:
   `C:\xampp\htdocs\skill-swap\database\skill_swap.sql`
4. Click the **Import** button at the bottom of the page.
5. The `skill_swap` database with all tables, constraints, and sample data will be created automatically.

### Step 4: Verify Database Configuration (Optional)
The database configuration in `config/db.php` is pre-configured for standard XAMPP defaults:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'skill_swap');
define('DB_USER', 'root');
define('DB_PASS', '');
```
If your MySQL has a password, update `DB_PASS` in `config/db.php`.

### Step 5: Launch the Application
Open your browser and visit:
```
http://localhost/skill-swap/
```

---

## 👥 Default Demo Credentials (Ready for Testing & Viva)

The database comes pre-seeded with realistic profiles and a mutual Two-Way match:

| Role | Name | Email / Username | Password | Key Skills / Features |
|---|---|---|---|---|
| **Admin** | Administrator | `admin@skillswap.com` / `admin` | `Admin@123` | Full access to Admin Console (`admin.php`) |
| **User A** | Sarah Chen | `sarah@skillswap.com` / `sarah_c` | `Password@123` | Teaches **Python**, wants **Photoshop** |
| **User B** | Alex Miller | `alex@skillswap.com` / `alex_m` | `Password@123` | Teaches **Photoshop**, wants **Python** (Mutual Match!) |
| **User C** | Priya Sharma | `priya@skillswap.com` / `priya_s` | `Password@123` | Teaches **SQL**, wants **Guitar** |
| **User D** | David Kim | `david@skillswap.com` / `david_k` | `Password@123` | Teaches **Video Editing**, wants **Spanish** |

> [!TIP]
> On the `login.php` page, click any of the **Quick Demo Logins** buttons to instantly fill the credentials without typing!

---

## 🔒 Security Implementations

- **Prepared Statements**: PDO statements prevent SQL injection.
- **Session Protection**: `session_regenerate_id(true)` prevents session fixation attacks; cookies set with `HttpOnly` and `SameSite=Lax`.
- **CSRF Tokens**: All mutating POST/AJAX requests validate tokens using `hash_equals()`.
- **Sanitized Filenames**: Profile uploads are checked via `finfo(FILEINFO_MIME_TYPE)` and saved with random hexadecimal filenames. Direct script execution inside the avatar directory is blocked via `.htaccess`.
- **Output Escaping**: All dynamic rendering passes through `htmlspecialchars()` to mitigate Cross-Site Scripting (XSS).

---

## 📖 Viva / Academic Presentation Guide

A dedicated document [DOCUMENTATION.md](file:///c:/Users/bhusa/Desktop/skilled-swap/DOCUMENTATION.md) is included with this project containing:
- In-depth architectural explanations.
- Why 3-tier architecture was chosen over monolithic script mixing.
- Flowcharts and set-theory formulations for the skill matching algorithm.
- 15+ common viva examination questions with model answers.

---

## 📄 License
Created for academic and educational project requirements. Open-source under the MIT License.
