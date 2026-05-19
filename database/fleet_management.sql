CREATE DATABASE IF NOT EXISTS fleet_management;
USE fleet_management;

DROP TABLE IF EXISTS bookings;

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guest_company_name VARCHAR(255) NOT NULL,
    car_type VARCHAR(100) NOT NULL,
    car_no VARCHAR(50) NOT NULL,
    operator_name VARCHAR(150) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    driver_name VARCHAR(150) NOT NULL,
    status ENUM('Pending', 'Confirm', 'In Service', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    remark TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO bookings (
    guest_company_name,
    car_type,
    car_no,
    operator_name,
    start_date,
    end_date,
    driver_name,
    status,
    remark
) VALUES
('Mr Yuichiro Hoshiro', 'Alphard', '3J/4760', 'Thin Thin Aye', '2026-03-06', '2026-03-07', 'Ko Han Win Aung', 'Confirm', 'Airport transfer'),
('Mr. Koichiro Yuhara & Mr. Masayasu', 'Ertiga Type II', '4N/4401', 'Nan Toe', '2026-03-19', '2026-03-22', 'Ko Han Win Aung', 'Confirm', 'Hotel pickup'),
('SCSK / Mr. Yoichiro Iida', 'Ertiga Type II', '4N/4401', 'Chu Saung Eain', '2026-03-09', '2026-03-12', 'Ko Han Win Aung', 'Pending', 'Need final confirmation'),
('Mitsubishi Corporation', 'Alphard', '4F/1641', 'Htet Htet Hlaing', '2026-03-02', '2026-03-03', 'Ko Pyae Phyo Aung', 'Completed', 'Handled successfully');
