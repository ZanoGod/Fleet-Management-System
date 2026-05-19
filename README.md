# Fleet Management System

Simple web-based fleet booking management system built with:

- PHP
- MySQL
- HTML
- CSS
- Bootstrap 5

The table structure follows the provided screenshot, but the `Even / Odd` column has been removed.

## Features

- Dashboard with booking summary cards
- Booking list with search and filters
- Create booking
- Edit booking
- Delete booking
- Bootstrap 5 responsive interface

## Project Structure

```text
fleet-management/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   └── database.php
├── database/
│   └── fleet_management.sql
├── includes/
│   ├── booking-form.php
│   ├── bootstrap.php
│   ├── footer.php
│   ├── header.php
│   └── helpers.php
├── create.php
├── delete.php
├── edit.php
├── index.php
└── README.md
```

## Setup

1. Create a MySQL database by importing [database/fleet_management.sql](/D:/Fleet%20Management%20System/fleet-management/database/fleet_management.sql).
2. Update MySQL username/password in [config/database.php](/D:/Fleet%20Management%20System/fleet-management/config/database.php) if your local setup is different.
3. Put this project inside your PHP web server folder, such as `htdocs` if you use XAMPP.
4. Open the project in your browser, for example:

```text
http://localhost/fleet-management/
```

## Notes

- Default database name: `fleet_management`
- Default MySQL username: `root`
- Default MySQL password: empty
- Main booking fields:
  - Guest / Company Name
  - Car Type
  - Car No
  - Operator
  - Start Date
  - End Date
  - Driver
  - Status
  - Remark
