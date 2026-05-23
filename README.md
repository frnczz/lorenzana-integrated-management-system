# LORINIMS - Lorenzana Food Corporation Integrated Management System

## Project Structure

```
lorinims/
├── assets/              # Static assets
│   ├── css/           # Stylesheets
│   │   └── style.css  # Main stylesheet (modern, responsive)
│   └── js/            # JavaScript files
│       └── sidebar.js # Sidebar functionality
│
├── layouts/            # Reusable layout components
│   ├── header.php     # Page header with navigation
│   ├── sidebar.php    # Sidebar navigation menu
│   └── footer.php     # Page footer
│
├── config/             # Configuration files (if needed)
│
├── login.php           # Login page
├── login_process.php   # Login authentication handler
├── logout.php          # Logout handler
│
├── admin_dashboard.php      # Admin dashboard
├── production_dashboard.php # Production staff dashboard
├── warehouse_dashboard.php  # Warehouse staff dashboard
├── quality_dashboard.php    # Quality Control dashboard
├── sales_dashboard.php       # Sales staff dashboard
│
├── production.php      # Production module
├── inventory.php       # Inventory management
├── procurement.php    # Procurement module
├── qc.php             # Quality Control module
├── sales.php          # Sales & Distribution module
├── accounting.php     # Accounting module
├── users.php          # User management (admin only)
├── driver_gps.php     # GPS tracking for delivery drivers
│
└── db_connect.php     # Database connection configuration
```

## User Roles & Access

The system supports the following user roles:

- **admin** - Full system access
- **production** - Production dashboard and records
- **warehouse** - Warehouse dashboard and inventory
- **qc** - Quality Control dashboard and inspections
- **accounting** - Accounting module access
- **sales** - Sales dashboard and order management
- **delivery** - GPS tracking for delivery drivers

## Features

- ✅ Modern, responsive design
- ✅ Role-based access control
- ✅ Mobile-friendly interface
- ✅ Sidebar navigation with collapse/expand
- ✅ Secure session management
- ✅ Dashboard views for each role

## Getting Started

1. Ensure XAMPP is running
2. Import the database schema to `lorinims_db`
3. Access the application at `http://localhost/lorinims/login.php`
4. Login with your credentials

## Technology Stack

- PHP (Server-side)
- MySQL (Database)
- HTML5/CSS3 (Frontend)
- JavaScript (Interactivity)

## Notes

- All pages require authentication
- Role-based redirects after login
- Mobile-responsive design (breakpoints at 768px and 480px)
- Modern CSS with variables for easy theming
