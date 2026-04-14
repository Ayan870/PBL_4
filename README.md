# PBL Management System

A web-based portal for managing Project-Based Learning (PBL) across university departments.

## Project Structure

```
pbl-management-system/
│
├── index.html                  ← Login page (entry point)
│
├── assets/
│   ├── css/
│   │   ├── common.css          ← Shared dashboard styles (sidebar, cards, etc.)
│   │   ├── style.css           ← Auth page styles (dark theme)
│   │   └── dashboard.css       ← Dashboard extended styles
│   └── js/
│       ├── app.js              ← Common dashboard logic (auth guard, user info, logout)
│       ├── auth.js             ← Login & register handlers
│       ├── proposal.js         ← Multi-step proposal form logic
│       └── charts.js           ← Canvas-based charts for analytics
│
├── pages/
│   ├── student/                ← Student role pages
│   │   ├── dashboard.html
│   │   ├── submit-proposal.html
│   │   ├── my-projects.html
│   │   ├── feedback.html
│   │   └── results.html
│   │
│   ├── supervisor/             ← Supervisor role pages
│   │   ├── dashboard.html
│   │   ├── review-proposals.html
│   │   ├── my-students.html
│   │   ├── evaluation.html
│   │   └── reports.html
│   │
│   ├── manager/                ← PBL Manager role pages
│   │   ├── dashboard.html
│   │   ├── users.html
│   │   ├── proposals.html
│   │   ├── evaluations.html
│   │   └── analytics.html
│   │
│   └── shared/                 ← Pages shared across roles
│       ├── messages.html
│       └── register.html
│
├── includes/                   ← Reusable PHP components (for backend phase)
│   ├── header.php
│   ├── footer.php
│   ├── auth_check.php
│   ├── sidebar_student.html
│   ├── sidebar_supervisor.html
│   └── sidebar_manager.html
│
├── config/
│   └── db.php                  ← Database connection (PDO — do not commit credentials)
│
├── uploads/                    ← Proposal document uploads (excluded from git)
│   └── .gitkeep
│
├── .gitignore
└── README.md
```

## Stakeholders & Their Dashboards

| Role | Folder | Dashboard |
|------|--------|-----------|
| Student | pages/student/ | Submit proposals, view feedback, results |
| Supervisor | pages/supervisor/ | Review proposals, evaluate, manage students |
| PBL Manager | pages/manager/ | Manage users, view all proposals, analytics |
| Chairman | *(coming soon)* | Assign subjects, view filtered results |
| Evaluator | *(coming soon)* | Temporary portal, evaluate on PBL day |

## Tech Stack
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons
- **Backend:** PHP (coming next phase)
- **Database:** MySQL (schema designed — see db schema docs)

## Setup
1. Clone the repo
2. Copy `config/db.php` and fill in your DB credentials
3. Import the SQL schema into your MySQL database
4. Run on a local server (XAMPP / WAMP / php -S localhost:8000)
5. Open `index.html` in your browser.
