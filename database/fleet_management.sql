CREATE DATABASE IF NOT EXISTS fleet_management;
USE fleet_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS operators;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS drivers;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE cars (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_type VARCHAR(100) NOT NULL,
    plate_no VARCHAR(50) NOT NULL UNIQUE,
    model_name VARCHAR(100) NOT NULL,
    seat_capacity TINYINT UNSIGNED NOT NULL DEFAULT 4,
    availability_status ENUM('Available', 'Assigned', 'Maintenance', 'Inactive') NOT NULL DEFAULT 'Available',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE drivers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    license_no VARCHAR(100) NULL UNIQUE,
    driver_status ENUM('Available', 'On Trip', 'Leave', 'Inactive') NOT NULL DEFAULT 'Available',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE operators (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL UNIQUE,
    phone_number VARCHAR(30) NULL,
    operator_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guest_company_name VARCHAR(255) NOT NULL,
    car_id INT UNSIGNED NULL,
    custom_car_name VARCHAR(255) NULL,
    driver_id INT UNSIGNED NULL,
    custom_driver_name VARCHAR(255) NULL,
    operator_id INT UNSIGNED NULL,
    operator_name VARCHAR(150) NOT NULL,
    even_odd ENUM('Even', 'Odd') NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Pending', 'Confirm', 'In Service', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    remark TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_car FOREIGN KEY (car_id) REFERENCES cars(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_operator FOREIGN KEY (operator_id) REFERENCES operators(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

INSERT INTO cars (car_type, plate_no, model_name, seat_capacity, availability_status, note) VALUES
('Alphard', '3J/4760', 'Toyota Alphard Executive', 7, 'Assigned', 'Preferred for VIP airport transfer'),
('Ertiga Type II', '4N/4401', 'Suzuki Ertiga Type II', 5, 'Available', 'Compact team transport'),
('Allion', '2K/6398', 'Toyota Allion Sedan', 4, 'Available', 'Comfortable city business trips'),
('Alphard', '4F/1641', 'Toyota Alphard Premium', 7, 'Maintenance', 'Maintenance booking scheduled'),
('Ertiga Type III', '9Q/6057', 'Suzuki Ertiga Type III', 5, 'Assigned', 'Assigned to multi-day client trip');

INSERT INTO drivers (full_name, phone_number, license_no, driver_status, note) VALUES
('Ko Han Win Aung', '09-111-222-333', 'D-MMR-001', 'Available', 'Good for airport and executive transfers'),
('Ko Pyae Phyo Aung', '09-222-333-444', 'D-MMR-002', 'On Trip', 'Assigned for long-distance duty'),
('Nan Toe', '09-333-444-555', 'D-MMR-003', 'Available', 'Frequently handles hotel pickup schedules'),
('Ngwe Sin Thar', '09-444-555-666', 'D-MMR-004', 'Leave', 'On approved leave'),
('Chaw Su May', '09-555-666-777', 'D-MMR-005', 'Available', 'Flexible standby driver');

INSERT INTO operators (full_name, phone_number, operator_status, note) VALUES
('Thin Thin Aye', '09-771-200-101', 'Active', 'Handles airport transfer coordination'),
('Nan Toe', '09-333-444-555', 'Active', 'Frequently manages hotel pickup bookings'),
('Chu Saung Eain', '09-771-200-103', 'Active', 'Coordinates corporate guest transportation'),
('Htet Htet Hlaing', '09-771-200-104', 'Active', 'Supports completed trip follow-up'),
('Ngwe Sin Thar', '09-444-555-666', 'Active', 'Supports multi-day guest movements');

INSERT INTO bookings (
    guest_company_name,
    car_id,
    custom_car_name,
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
('Mr Yuichiro Hoshiro', 1, NULL, 1, NULL, 1, 'Thin Thin Aye', 'Even', '2026-03-06', '2026-03-07', 'Confirm', 'Airport transfer'),
('Mr. Koichiro Yuhara & Mr. Masayasu', 2, NULL, 3, NULL, 2, 'Nan Toe', 'Odd', '2026-03-19', '2026-03-22', 'Confirm', 'Hotel pickup'),
('SCSK / Mr. Yoichiro Iida', 2, NULL, 1, NULL, 3, 'Chu Saung Eain', NULL, '2026-03-09', '2026-03-12', 'Pending', 'Need final confirmation'),
('Mitsubishi Corporation', 4, NULL, 2, NULL, 4, 'Htet Htet Hlaing', 'Even', '2026-03-02', '2026-03-03', 'Completed', 'Handled successfully'),
('Nikken International Myanmar Co., Ltd.', 5, NULL, 5, NULL, 5, 'Ngwe Sin Thar', 'Odd', '2026-03-05', '2026-03-07', 'In Service', 'Three-day guest movement support');
