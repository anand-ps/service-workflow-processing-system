# On-Demand Local Service Booking Application (BCSP-064)

A web-based platform that connects local service seekers with verified service providers (electricians, plumbers, carpenters, painters, mechanics, and cleaners) with real-time status tracking, ratings, and administrative oversight.

---

## Project Structure

This project follows a strict separation between the static frontend client and the PHP REST backend:

```text
service-workflow-processing-system/
├── frontend/                     # Client interface (HTML5, CSS3, ES6 JavaScript)
│   ├── css/
│   │   └── style.css             # Responsive styling & status badge indicators
│   ├── js/
│   │   ├── config.js             # API base URL configuration & fetch helper
│   │   ├── auth.js               # Session management & role-based routing
│   │   ├── user.js               # Service browsing, booking, and reviews
│   │   ├── provider.js           # Request queues, status workflow management
│   │   └── admin.js              # Provider verification & system analytics
│   ├── index.html                # Service directory & search portal
│   ├── login.html                # Unified authentication portal
│   ├── register.html             # User & Provider registration
│   ├── user-dashboard.html       # Customer booking manager & feedback form
│   ├── provider-dashboard.html   # Provider request lifecycle manager
│   └── admin-dashboard.html      # Admin portal & analytical reports
│
├── backend/                      # Backend RESTful JSON API (PHP & MySQL PDO)
│   ├── config/
│   │   └── db.php                # Database connection & authentication helpers
│   ├── api/
│   │   ├── auth/                 # login.php, register.php, logout.php, me.php
│   │   ├── services/             # list.php, create.php
│   │   ├── providers/            # list.php, pending.php, approve.php
│   │   ├── bookings/             # create.php, list.php, update-status.php
│   │   ├── feedback/             # create.php, list.php
│   │   └── reports/              # summary.php (status breakdown & provider performance)
│   ├── setup.php                 # 1-Click database installer & seed data populator
│   └── database.sql              # Raw SQL database schema
│
└── README.md                     # Setup and demonstration instructions
```

---

## 2-Minute Quick Start Guide

### Step 1: Install & Start XAMPP
1. Download and install **XAMPP** from [apachefriends.org](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel** and click **Start** next to:
   - **Apache**
   - **MySQL**

### Step 2: Place the Project in `htdocs`
Copy the project folder `service-workflow-processing-system` into your XAMPP web root directory:
```text
C:\xampp\htdocs\service-workflow-processing-system\
```

### Step 3: Run the 1-Click Database Setup
Open your web browser and navigate to:
```text
http://localhost/service-workflow-processing-system/backend/setup.php
```
This automated installer will:
- Connect to MySQL automatically.
- Create the database `service_booking_db`.
- Build all 5 relational tables (`users`, `services`, `providers`, `bookings`, `feedback`).
- Seed sample accounts, service categories, active bookings, and customer reviews.

### Step 4: Open the Application
Open the frontend in your browser:
```text
http://localhost/service-workflow-processing-system/frontend/index.html
```

---

## Pre-Configured Demonstration Accounts

The setup script pre-populates working demo accounts for your evaluation:

| Role | Email | Password | Details |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@services.com` | `admin123` | Full access to approvals, master bookings, and reports |
| **Customer** | `rahul@gmail.com` | `user123` | Client account with active and past service bookings |
| **Service Provider (Approved)** | `rajesh@electrician.com` | `provider123` | Verified electrician with incoming booking requests |
| **Service Provider (Pending)** | `manoj@painter.com` | `provider123` | Demonstrates the admin verification queue |

---

## Core System Modules

1. **User / Customer Module**:
   - Filter and search local professionals by category or location.
   - Schedule bookings with preferred date, time slot, and address.
   - Live status tracking (`Pending` &rarr; `Accepted` &rarr; `On the Way` &rarr; `Completed`).
   - Rate providers (1 to 5 stars) and submit written reviews upon job completion.

2. **Service Provider Module**:
   - Registration with category selection, experience, hourly rates, and operating area.
   - Incoming request queue with customer contact info and problem details.
   - One-click request acceptance or rejection.
   - Step-by-step job progress updater (`Accepted` &rarr; `On the Way` &rarr; `Completed`).

3. **Administrator Module**:
   - Provider verification workflow (approve or reject pending applicants).
   - Add new service categories on the fly.
   - Audit trail of all bookings across the platform.
   - Analytical reporting (Section 7):
     - Booking status breakdown report.
     - User and provider growth metrics.
     - Provider performance report (completed jobs and average ratings).
