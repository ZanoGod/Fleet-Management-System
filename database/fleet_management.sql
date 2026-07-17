CREATE DATABASE IF NOT EXISTS `fleet-beta`;
USE `fleet-beta`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS monthly_contracts;
DROP TABLE IF EXISTS monthly_cars;
DROP TABLE IF EXISTS monthly_drivers;
DROP TABLE IF EXISTS operators;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;


-- =========================
-- ADMINS TABLE
-- =========================

CREATE TABLE admins (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(80) NOT NULL UNIQUE,

    email VARCHAR(150) NULL UNIQUE,

    password_hash VARCHAR(255) NOT NULL,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    last_login_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);


INSERT INTO admins (
    username,
    email,
    password_hash,
    is_active
) VALUES (
    'admin',
    NULL,
    '$2y$10$uXe5tv/WhKPGervyev/m7.HIPHBSr6gbnCPZXOQOBVYX21n20J3Yu',
    1
);





-- =========================
-- CARS TABLE
-- =========================

CREATE TABLE cars (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    car_type VARCHAR(100) NOT NULL,

    plate_no VARCHAR(50) NOT NULL UNIQUE,

    model_name VARCHAR(100) NOT NULL,

    seat_capacity TINYINT UNSIGNED NOT NULL DEFAULT 4,

    availability_status ENUM(
        'Available',
        'Assigned',
        'Maintenance',
        'Inactive'
    ) NOT NULL DEFAULT 'Available',

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);





-- =========================
-- DRIVERS TABLE
-- =========================

CREATE TABLE drivers (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(150) NOT NULL,

    phone_number VARCHAR(30) NOT NULL,

    license_no VARCHAR(100) NULL UNIQUE,

    driver_status ENUM(
        'Available',
        'On Trip',
        'Leave',
        'Inactive'
    ) NOT NULL DEFAULT 'Available',

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);





-- =========================
-- MONTHLY CARS TABLE
-- =========================

CREATE TABLE monthly_cars (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    car_type VARCHAR(100) NOT NULL,

    plate_no VARCHAR(50) NOT NULL UNIQUE,

    model_name VARCHAR(100) NOT NULL,

    seat_capacity TINYINT UNSIGNED NOT NULL DEFAULT 4,

    availability_status ENUM(
        'Available',
        'Assigned',
        'Maintenance',
        'Inactive'
    ) NOT NULL DEFAULT 'Available',

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);





-- =========================
-- MONTHLY DRIVERS TABLE
-- =========================

CREATE TABLE monthly_drivers (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(150) NOT NULL,

    phone_number VARCHAR(30) NOT NULL,

    license_no VARCHAR(100) NULL UNIQUE,

    driver_status ENUM(
        'Available',
        'Assigned',
        'Leave',
        'Inactive'
    ) NOT NULL DEFAULT 'Available',

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);





-- =========================
-- OPERATORS TABLE
-- =========================

CREATE TABLE operators (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(150) NOT NULL UNIQUE,

    phone_number VARCHAR(30) NULL,

    operator_status ENUM(
        'Active',
        'Inactive'
    ) NOT NULL DEFAULT 'Active',

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);





-- =========================
-- BOOKINGS TABLE
-- =========================

CREATE TABLE bookings (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    guest_company_name VARCHAR(255) NOT NULL,

    car_id INT UNSIGNED NULL,

    custom_car_name VARCHAR(255) NULL,

    secondary_car_id INT UNSIGNED NULL,

    driver_id INT UNSIGNED NULL,

    custom_driver_name VARCHAR(255) NULL,

    operator_id INT UNSIGNED NULL,

    operator_name VARCHAR(150) NOT NULL,

    even_odd ENUM('Even', 'Odd') NULL,

    start_date DATE NOT NULL,

    end_date DATE NOT NULL,

    status ENUM(
        'Pending',
        'Confirm',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    remark TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,



    CONSTRAINT fk_bookings_car
    FOREIGN KEY (car_id)
    REFERENCES cars(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,



    CONSTRAINT fk_bookings_driver
    FOREIGN KEY (driver_id)
    REFERENCES drivers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

    CONSTRAINT fk_bookings_secondary_car
    FOREIGN KEY (secondary_car_id)
    REFERENCES cars(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,



    CONSTRAINT fk_bookings_operator
    FOREIGN KEY (operator_id)
    REFERENCES operators(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL

);





-- =========================
-- MONTHLY CONTRACTS TABLE
-- =========================

CREATE TABLE monthly_contracts (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    contract_no VARCHAR(50) NOT NULL UNIQUE,

    customer_name VARCHAR(255) NOT NULL,

    customer_phone VARCHAR(50) NULL,

    customer_email VARCHAR(150) NULL,

    customer_address TEXT NULL,

    assignment_type ENUM(
        'Customer Only',
        'Vehicle Only',
        'Vehicle and Driver'
    ) NOT NULL DEFAULT 'Vehicle Only',

    car_id INT UNSIGNED NULL,

    driver_id INT UNSIGNED NULL,

    operator_id INT UNSIGNED NULL,

    operator_name VARCHAR(150) NULL,

    start_date DATE NOT NULL,

    end_date DATE NOT NULL,

    monthly_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    security_deposit DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    payment_status ENUM(
        'Pending',
        'Paid',
        'Overdue'
    ) NOT NULL DEFAULT 'Pending',

    status ENUM(
        'Draft',
        'Active',
        'Expired',
        'Renewed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Active',

    renewal_parent_id INT UNSIGNED NULL,

    note TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_monthly_contracts_car_id (car_id),

    INDEX idx_monthly_contracts_driver_id (driver_id),

    INDEX idx_monthly_contracts_operator_id (operator_id),

    INDEX idx_monthly_contracts_status_end_date (status, end_date),

    INDEX idx_monthly_contracts_renewal_parent_id (renewal_parent_id),

    CONSTRAINT fk_monthly_contracts_car
    FOREIGN KEY (car_id)
    REFERENCES monthly_cars(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

    CONSTRAINT fk_monthly_contracts_driver
    FOREIGN KEY (driver_id)
    REFERENCES monthly_drivers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

    CONSTRAINT fk_monthly_contracts_operator
    FOREIGN KEY (operator_id)
    REFERENCES operators(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

    CONSTRAINT fk_monthly_contracts_parent
    FOREIGN KEY (renewal_parent_id)
    REFERENCES monthly_contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL

);





-- =========================
-- SAMPLE CARS
-- =========================

INSERT INTO cars (
    car_type,
    plate_no,
    model_name,
    seat_capacity,
    availability_status,
    note
) VALUES

('Alphard', '3J/4760', 'Toyota Alphard Executive', 7, 'Assigned', 'Preferred for VIP airport transfer'),

('Ertiga Type II', '4N/4401', 'Suzuki Ertiga Type II', 5, 'Available', 'Compact team transport'),

('Allion', '2K/6398', 'Toyota Allion Sedan', 4, 'Available', 'Comfortable city business trips'),

('Alphard', '4F/1641', 'Toyota Alphard Premium', 7, 'Maintenance', 'Maintenance booking scheduled'),

('Ertiga Type III', '9Q/6057', 'Suzuki Ertiga Type III', 5, 'Assigned', 'Assigned to multi-day client trip');





-- =========================
-- SAMPLE DRIVERS
-- =========================

INSERT INTO drivers (
    full_name,
    phone_number,
    license_no,
    driver_status,
    note
) VALUES

('Ko Han Win Aung', '09-111-222-333', 'D-MMR-001', 'Available', 'Good for airport and executive transfers'),

('Ko Pyae Phyo Aung', '09-222-333-444', 'D-MMR-002', 'On Trip', 'Assigned for long-distance duty'),

('Nan Toe', '09-333-444-555', 'D-MMR-003', 'Available', 'Frequently handles hotel pickup schedules'),

('Ngwe Sin Thar', '09-444-555-666', 'D-MMR-004', 'Leave', 'On approved leave'),

('Chaw Su May', '09-555-666-777', 'D-MMR-005', 'Available', 'Flexible standby driver');





-- =========================
-- SAMPLE MONTHLY CARS
-- =========================

INSERT INTO monthly_cars (
    car_type,
    plate_no,
    model_name,
    seat_capacity,
    availability_status,
    note
) VALUES

('Monthly Alphard', 'MC-3J/4760', 'Toyota Alphard Monthly Executive', 7, 'Assigned', 'Dedicated monthly executive vehicle'),

('Monthly Ertiga', 'MC-4N/4401', 'Suzuki Ertiga Monthly', 5, 'Available', 'Monthly contract compact transport'),

('Monthly Allion', 'MC-2K/6398', 'Toyota Allion Monthly Sedan', 4, 'Available', 'Monthly city business vehicle');





-- =========================
-- SAMPLE MONTHLY DRIVERS
-- =========================

INSERT INTO monthly_drivers (
    full_name,
    phone_number,
    license_no,
    driver_status,
    note
) VALUES

('Ko Monthly Han', '09-811-222-333', 'MD-MMR-001', 'Available', 'Monthly executive contract driver'),

('Ko Monthly Pyae', '09-822-333-444', 'MD-MMR-002', 'Assigned', 'Assigned to monthly customer'),

('Daw Monthly Su', '09-833-444-555', 'MD-MMR-003', 'Available', 'Monthly standby driver');





-- =========================
-- SAMPLE OPERATORS
-- =========================

INSERT INTO operators (
    full_name,
    phone_number,
    operator_status,
    note
) VALUES

('Thin Thin Aye', '09-771-200-101', 'Active', 'Handles airport transfer coordination'),

('Nan Toe', '09-333-444-555', 'Active', 'Frequently manages hotel pickup bookings'),

('Chu Saung Eain', '09-771-200-103', 'Active', 'Coordinates corporate guest transportation'),

('Htet Htet Hlaing', '09-771-200-104', 'Active', 'Supports completed trip follow-up'),

('Ngwe Sin Thar', '09-444-555-666', 'Active', 'Supports multi-day guest movements');





-- =========================
-- SAMPLE BOOKINGS
-- =========================

INSERT INTO bookings (

    guest_company_name,
    car_id,
    custom_car_name,
    secondary_car_id,
    driver_id,
    custom_driver_name,
    operator_id,
    operator_name,
    even_odd,
    start_date,
    end_date,
    status,
    remark

) VALUES

(
    'Mr Yuichiro Hoshiro',
    1,
    NULL,
    NULL,
    1,
    NULL,
    1,
    'Thin Thin Aye',
    'Even',
    '2026-03-06',
    '2026-03-07',
    'Confirm',
    'Airport transfer'
),

(
    'Mr. Koichiro Yuhara & Mr. Masayasu',
    2,
    NULL,
    NULL,
    3,
    NULL,
    2,
    'Nan Toe',
    'Odd',
    '2026-03-19',
    '2026-03-22',
    'Confirm',
    'Hotel pickup'
),

(
    'SCSK / Mr. Yoichiro Iida',
    2,
    NULL,
    NULL,
    1,
    NULL,
    3,
    'Chu Saung Eain',
    NULL,
    '2026-03-09',
    '2026-03-12',
    'Pending',
    'Need final confirmation'
),

(
    'Mitsubishi Corporation',
    4,
    NULL,
    NULL,
    2,
    NULL,
    4,
    'Htet Htet Hlaing',
    'Even',
    '2026-03-02',
    '2026-03-03',
    'Completed',
    'Handled successfully'
),

(
    'Nikken International Myanmar Co., Ltd.',
    5,
    NULL,
    NULL,
    5,
    NULL,
    5,
    'Ngwe Sin Thar',
    'Odd',
    '2026-03-05',
    '2026-03-07',
    'Confirm',
    'Three-day guest movement support'
);





-- =========================
-- SAMPLE MONTHLY CONTRACTS
-- =========================

INSERT INTO monthly_contracts (

    contract_no,
    customer_name,
    customer_phone,
    customer_email,
    customer_address,
    assignment_type,
    car_id,
    driver_id,
    operator_id,
    operator_name,
    start_date,
    end_date,
    monthly_rate,
    security_deposit,
    payment_status,
    status,
    renewal_parent_id,
    note

) VALUES

(
    'MC-202607-0001',
    'SCSK Myanmar',
    '09-700-111-222',
    'admin@scsk.example',
    'Yangon',
    'Vehicle and Driver',
    1,
    1,
    1,
    'Thin Thin Aye',
    '2026-07-01',
    '2026-07-31',
    2500000.00,
    500000.00,
    'Paid',
    'Active',
    NULL,
    'Monthly executive vehicle with assigned driver.'
),

(
    'MC-202607-0002',
    'Mitsubishi Corporation',
    '09-700-333-444',
    'fleet@mitsubishi.example',
    'Bahan, Yangon',
    'Vehicle Only',
    2,
    NULL,
    3,
    'Chu Saung Eain',
    '2026-07-05',
    '2026-08-04',
    1800000.00,
    300000.00,
    'Pending',
    'Active',
    NULL,
    'Vehicle-only monthly contract.'
),

(
    'MC-202606-0003',
    'Nikken International Myanmar Co., Ltd.',
    '09-700-555-666',
    NULL,
    'Hlaing, Yangon',
    'Customer Only',
    NULL,
    NULL,
    5,
    'Ngwe Sin Thar',
    '2026-06-01',
    '2026-06-30',
    0.00,
    0.00,
    'Overdue',
    'Expired',
    NULL,
    'Customer contract record kept without assigned resources.'
);
