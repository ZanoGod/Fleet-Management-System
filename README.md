# Fleet Management System

A web-based Fleet Management System developed using PHP, MySQL, Bootstrap 5, HTML, CSS, and JavaScript.

The system is designed to manage company vehicle bookings, vehicle information, drivers, operators, and provide a centralized dashboard for daily operations.

---

## Features

### Dashboard
- Summary statistics
- Active bookings
- Fleet overview
- Recent bookings
- Operational insights

### Booking Management
- Create bookings
- Edit bookings
- Delete bookings
- View booking history

### Fleet Management
- Vehicle registration
- Vehicle details
- Vehicle status
- Fleet information

### Driver Management
- Driver registration
- Driver information
- Driver assignment

### Operator Management
- Operator management
- Contact information
- Assignment management

### Authentication
- Secure Login
- Logout
- Session Management

---

## Technology Stack

Backend
- PHP 8+
- MySQL

Frontend
- Bootstrap 5
- HTML5
- CSS3
- JavaScript

---

## Project Structure

```
fleet-management/
│
├── index.php
├── login.php
├── logout.php
│
├── bookings.php
├── create.php
├── edit.php
├── delete.php
│
├── cars.php
├── car-create.php
├── car-edit.php
├── car-delete.php
│
├── drivers.php
├── driver-create.php
├── driver-edit.php
├── driver-delete.php
│
├── operators.php
├── operator-create.php
├── operator-edit.php
├── operator-delete.php
│
├── components/
│   ├── header.php
│   ├── footer.php
│   ├── booking-form.php
│   ├── car-form.php
│   ├── driver-form.php
│   ├── operator-form.php
│   ├── summary-grid.php
│   ├── metric-rows.php
│   ├── insights-column.php
│   └── recent-bookings-card.php
│
├── includes/
│   ├── bootstrap.php
│   ├── dashboard-data.php
│   ├── helpers.php
│   └── messages.php
│
└── README.md
```

---

## Core Modules

### Dashboard
Displays operational summaries including:

- Total Vehicles
- Total Drivers
- Total Operators
- Total Bookings
- Recent Bookings
- Business Insights
---


### Booking Module

The booking module allows users to:

- Register new bookings
- Update booking information
- Delete bookings
- View booking history

---


### Fleet Module
Stores vehicle information including:

- Vehicle Number
- Vehicle Name
- Vehicle Type
- Status

---


### Driver Module

Maintains driver information including:

- Driver Name
- Contact Number
- Assigned Vehicle

---


### Operator Module
Stores operator information including:

- Company
- Contact Person
- Phone Number

---

## Security

Current implementation

- Login Authentication
- Session Management

Recommended improvements

- Password Hashing
- CSRF Protection
- SQL Prepared Statements
- Role-Based Access Control
- Activity Logs

---

## Future Enhancements

- Customer Management
- Company Management
- Invoice Module
- Payment Tracking
- Vehicle Maintenance
- Fuel Consumption
- Insurance Management
- Driver License Expiry Alerts
- Calendar Booking View
- Booking Conflict Detection
- Dashboard Charts
- Excel Export
- PDF Reports
- Notification System
- Email Notifications

---

## Requirements

- PHP 8+
- MySQL 8+
- Apache / XAMPP
- Bootstrap 5

---

## Installation

1. Clone or download the project.
2. Import the MySQL database.
3. Configure database credentials.
4. Place the project inside the web server directory.
5. Start Apache and MySQL.
6. Open the project in your browser.

```
http://localhost/fleet-management/
```

---

## Developed For

Golden Support Services Co., Ltd.

Fleet Operation Department

---

## Version

Version 1.0