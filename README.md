# Campus Academic Resource & Notes Sharing Portal
> **A Full-Stack University Academic Repository & Notes Sharing Web Application**  
> Built with **PHP (PDO)**, **MySQL (XAMPP)**, **HTML5**, **CSS3**, and **JavaScript** for DBMS Academic Project Evaluation.

---

## 🌟 Executive Summary

**Campus Academic Resource & Notes Sharing Portal** is an enterprise-grade academic repository designed for university campuses. It allows students, faculty members, database updaters, and system administrators to securely upload, categorize, peer-review, verify, search, and download academic course materials (lecture notes, solved exam question banks, lab sheets, and reference materials).

---

## 🛡️ Role-Based Access Control (RBAC) System

| Role | Default Email | Password | Primary Responsibilities |
| :--- | :--- | :--- | :--- |
| **Super Administrator** | `admin@campus.edu` | `password123` | Full system control, role delegation, department/course catalog management, audit logs, database diagnostics, and SQL dump export. |
| **Database Updater / Moderator** | `updater@campus.edu` | `password123` | Content quality vetting, course categorization, metadata updates, report resolutions, and pending queue approval via Stored Procedures. |
| **Faculty Member / Teacher** | `faculty@campus.edu` | `password123` | Upload verified lecture notes, question banks, track student download metrics, and review student feedback. |
| **Student / Learner** | `student@campus.edu` | `password123` | Advanced search & filtering, download notes, submit 1-5 star ratings & reviews, bookmark favorites, and engage in Q&A discussions. |

---

## 🗄️ DBMS Course Concepts & Architecture

The database architecture has been rigorously designed to satisfy all academic DBMS coursework guidelines:

### 1. **3NF Relational Normalization**
- **1NF (First Normal Form)**: Atomic column values, primary keys for each relation (`user_id`, `resource_id`, `course_id`, etc.).
- **2NF (Second Normal Form)**: Elimination of partial dependencies; all non-key attributes fully functionally dependent on primary keys.
- **3NF (Third Normal Form)**: Elimination of transitive dependencies; roles, departments, categories, courses, and ratings separated into dedicated relational tables.

### 2. **Database Schema (13 Relational Tables)**
1. `roles`: Role definitions (Admin, Moderator, Faculty, Student).
2. `departments`: Academic faculties (CSE, EEE, BBA, MATH, ENG).
3. `semesters`: Academic terms (Spring 2026, Summer 2026, Fall 2026).
4. `courses`: Course codes, titles, credit hours, and syllabus outlines.
5. `categories`: Lecture notes, question banks, lab manuals, slides, solutions.
6. `users`: User profiles with encrypted credentials and academic IDs.
7. `resources`: Master table for documents, file sizes, MIME types, rating metrics.
8. `reviews`: 1-5 star evaluations and qualitative reviews with composite unique constraints (`user_id`, `resource_id`).
9. `comments`: Nested discussions and Q&A threads.
10. `bookmarks`: Personal user favorites.
11. `download_logs`: Timestamped download audit tracking with IP addresses.
12. `activity_logs`: Enterprise audit trail of system events and administrative actions.
13. `reports`: Content moderation and broken link reporting tickets.

### 3. **Automated Database Triggers**
- `trg_after_review_insert`: Recalculates `resources.avg_rating` and `rating_count` automatically when a review is added.
- `trg_after_review_update`: Automatically synchronizes `avg_rating` on review updates.
- `trg_after_review_delete`: Re-computes `avg_rating` if a review is removed.
- `trg_after_download_log_insert`: Increments `resources.download_count` automatically upon every logged file stream.

### 4. **Analytical Database Views**
- `view_top_rated_resources`: Aggregates approved resources joined with department, course, and author metrics for top charts.
- `view_department_statistics`: Department-level summary of total courses, total published notes, downloads, and enrolled students.
- `view_user_contributions`: Author activity ranking, approved uploads ratio, and average author rating.

### 5. **Stored Procedures & Transactions**
- `sp_approve_resource(resource_id, moderator_id, notes)`: Atomically updates resource status to approved and commits an audit log entry in a single atomic transaction with rollback handling.
- `sp_get_course_resources(p_course_id)`: Fetches all approved course materials sorted by submission date.

---

## 📂 Project Directory Structure

```
campus_notes_portal/
├── config/
│   ├── config.php              # Global site paths, constants, and session settings
│   ├── database.php            # PDO singleton database connection & error handler
│   └── security.php            # CSRF protection, input sanitization, and audit logger
├── database/
│   ├── schema.sql              # Master DDL + DML (tables, triggers, views, procedures, seed data)
│   └── db_setup.php            # Automated one-click web installer & diagnostic wizard
├── includes/
│   ├── header.php              # Global HTML head, meta tags, and Bootstrap 5 CDN
│   ├── navbar.php              # Dynamic role-based navigation bar
│   ├── footer.php              # Responsive footer and JavaScript bundle
│   ├── helpers.php             # Flash messages, star rating renderer, size formatters
│   └── auth_middleware.php     # RBAC route guards (requireAuth, requireRole)
├── actions/
│   ├── auth_action.php         # Login, Register, and Logout controller
│   ├── resource_action.php     # Upload, Moderate, Delete, and Stored Procedure caller
│   ├── review_action.php       # Ratings, Reviews, and Q&A comments handler
│   ├── bookmark_action.php     # AJAX Bookmark toggle endpoint
│   ├── download_action.php     # Secure file download streamer with trigger logging
│   └── admin_action.php        # User roles, Depts, Courses CRUD & SQL exporter
├── assets/
│   ├── css/
│   │   ├── style.css           # Modern theme styling, variables, glassmorphism
│   │   └── dashboard.css       # Sidebar, KPI stat cards, data tables, and badges
│   ├── js/
│   │   └── main.js             # Client interactivity, AJAX search, dynamic course filter
│   └── uploads/
│       └── notes/              # Physical storage directory for PDF/DOCX materials
├── admin/
│   ├── index.php               # Admin Dashboard with view-backed KPI metrics
│   ├── users.php               # User & Role privilege delegation
│   ├── departments.php         # Department & Course CRUD manager
│   ├── resources.php           # Resource Moderation & Catalog Table
│   ├── logs.php                # System Audit Trail viewer
│   └── db_status.php           # DBMS diagnostics (information_schema & triggers)
├── moderator/
│   ├── index.php               # Database Updater & Moderator overview
│   ├── pending_reviews.php     # Vetting queue (1-click Approve / Reject)
│   └── reported_items.php      # User reports resolution center
├── faculty/
│   ├── index.php               # Faculty overview dashboard
│   ├── upload.php              # Material upload wizard with course selector
│   └── my_resources.php        # Published documents manager
├── student/
│   ├── index.php               # Student portal overview
│   ├── bookmarks.php           # Saved notes collection
│   └── my_downloads.php        # Download history log
├── index.php                   # Public Homepage with live search & top charts
├── explore.php                 # Multi-filter catalog (Dept, Course, Category, Sort)
├── resource_details.php        # Single resource viewer, ratings, and discussion
├── login.php                   # Secure authentication portal with 1-click demo filler
├── register.php                # Student and Faculty registration form
├── profile.php                 # User profile settings and password update
└── README.md                   # Complete documentation and viva defense guide
```

---

## 🚀 Setup & Installation (XAMPP / MySQL)

### Step 1: Place Project in XAMPP
Copy the `campus_notes_portal` folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\campus_notes_portal
```

### Step 2: Start Services in XAMPP Control Panel
1. Open **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 3: Run the Automated Database Installer
Open your browser and navigate to:
```
http://localhost/campus_notes_portal/database/db_setup.php
```
Click **"Initialize Database & Demo Records"**. This will automatically:
- Create `campus_notes_db`
- Execute `schema.sql` (Tables, Triggers, Views, Stored Procedures)
- Seed sample users, courses, departments, notes, and reviews.

*(Alternative Method: You can also import `database/schema.sql` directly into `http://localhost/phpmyadmin`)*.

### Step 4: Open the Web Portal
Navigate to:
```
http://localhost/campus_notes_portal/
```
You can click **Sign In** and use the **1-Click Quick Demo Login** buttons for instant access to any role!

---

## 🎓 DBMS Viva & Defense Quick Reference

### Q1: How is 3NF normalization maintained in this system?
> **Answer**: All entities have primary keys. Attributes depend solely on the primary key without partial dependencies. Transitive dependencies are eliminated by separating departments, courses, roles, categories, and reviews into distinct normalized tables linked by foreign keys with referential integrity constraints (`ON DELETE CASCADE` / `ON UPDATE CASCADE`).

### Q2: Where and why are Triggers used in your project?
> **Answer**: Triggers (`trg_after_review_insert`, `trg_after_review_update`, `trg_after_review_delete`) automatically calculate and update the aggregate `avg_rating` in the `resources` table whenever reviews are modified. Another trigger (`trg_after_download_log_insert`) increments `download_count` upon insertion into `download_logs`. This ensures ACID consistency without manual application recalculations.

### Q3: What is the purpose of Database Views in this portal?
> **Answer**: Views like `view_top_rated_resources` and `view_department_statistics` pre-join multiple tables and compute real-time analytical metrics, optimizing query reusability and abstracting complex joins from the presentation layer.

### Q4: How are Stored Procedures utilized?
> **Answer**: `sp_approve_resource` encapsulates the moderation approval workflow within a database transaction, guaranteeing that resource status update and activity audit logging happen atomically.

---
*Developed for University DBMS Academic Course Project Evaluation.*
