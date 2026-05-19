# Fleet Management System

Web-based fleet management system built with:

- PHP
- MySQL
- HTML
- CSS
- Bootstrap 5
- Small JavaScript for the responsive sidebar

The layout follows your requested warm palette:

- `#F6F1E9`
- `#FFD93D`
- `#FF9A00`
- `#4F200D`

The booking table does not include the `Even / Odd` column.

## Modules

- Dashboard
- Bookings
- Cars / Fleets
- Drivers
- Reports

## Features

- Responsive sidebar navigation
- Dashboard summary cards
- Booking CRUD
- Car CRUD
- Driver CRUD
- Reports overview
- Responsive Bootstrap 5 interface

## Database Structure

This project now uses three main tables:

- `cars`
- `drivers`
- `bookings`

Bookings are linked to the car and driver master records.

## Setup

1. Import [database/fleet_management.sql](/D:/Fleet%20Management%20System/fleet-management/database/fleet_management.sql) into MySQL.
2. Update [config/database.php](/D:/Fleet%20Management%20System/fleet-management/config/database.php) if your MySQL username or password is different.
3. Put the project in your PHP server folder such as `htdocs` if you use XAMPP.
4. Open the project in your browser:

```text
http://localhost/fleet-management/
```

## Main Pages

- Dashboard: [index.php](/D:/Fleet%20Management%20System/fleet-management/index.php)
- Bookings: [bookings.php](/D:/Fleet%20Management%20System/fleet-management/bookings.php)
- Cars / Fleets: [cars.php](/D:/Fleet%20Management%20System/fleet-management/cars.php)
- Drivers: [drivers.php](/D:/Fleet%20Management%20System/fleet-management/drivers.php)
- Reports: [reports.php](/D:/Fleet%20Management%20System/fleet-management/reports.php)

## Important Note

If you already imported the old SQL file from the earlier version, import this updated SQL file again because the database structure now includes separate `cars` and `drivers` tables.
