-- Flight Booking & Management System - MySQL DDL
-- Run in MySQL Workbench, phpMyAdmin, or: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS flightbook1
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE flightbook1;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS flights;
DROP TABLE IF EXISTS aircraft;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS airports;
DROP TABLE IF EXISTS passengers;

SET FOREIGN_KEY_CHECKS = 1;

-- Passengers (role: passenger | admin)
CREATE TABLE passengers (
    pass_id   INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL,
    email     VARCHAR(150) NOT NULL UNIQUE,
    phone     VARCHAR(20)  NOT NULL,
    password  VARCHAR(255) NOT NULL,
    role      ENUM('passenger', 'admin') NOT NULL DEFAULT 'passenger'
);

-- Airports
CREATE TABLE airports (
    airp_id      INT AUTO_INCREMENT PRIMARY KEY,
    airport_name VARCHAR(150) NOT NULL,
    city         VARCHAR(100) NOT NULL,
    country      VARCHAR(100) NOT NULL
);

-- Routes
CREATE TABLE routes (
    route_id   INT AUTO_INCREMENT PRIMARY KEY,
    source_id  INT NOT NULL,
    dest_id    INT NOT NULL,
    distance   DECIMAL(10,2) NOT NULL CHECK (distance > 0),
    CONSTRAINT fk_route_source FOREIGN KEY (source_id) REFERENCES airports(airp_id),
    CONSTRAINT fk_route_dest   FOREIGN KEY (dest_id)   REFERENCES airports(airp_id),
    CONSTRAINT chk_route_diff  CHECK (source_id <> dest_id)
);

-- Aircraft
CREATE TABLE aircraft (
    tail_no  VARCHAR(20) PRIMARY KEY,
    model    VARCHAR(100) NOT NULL,
    capacity INT NOT NULL CHECK (capacity > 0)
);

-- Flights
CREATE TABLE flights (
    flight_id    INT AUTO_INCREMENT PRIMARY KEY,
    route_id     INT NOT NULL,
    tail_no      VARCHAR(20) NOT NULL,
    dept_time    DATETIME NOT NULL,
    seats_avail  INT NOT NULL CHECK (seats_avail >= 0),
    CONSTRAINT fk_flight_route    FOREIGN KEY (route_id) REFERENCES routes(route_id),
    CONSTRAINT fk_flight_aircraft FOREIGN KEY (tail_no)  REFERENCES aircraft(tail_no)
);

-- Bookings
CREATE TABLE bookings (
    book_id   INT AUTO_INCREMENT PRIMARY KEY,
    pass_id   INT NOT NULL,
    flight_id INT NOT NULL,
    seat_no   VARCHAR(10) NOT NULL,
    status    ENUM('Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    fare      DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_booking_passenger FOREIGN KEY (pass_id) REFERENCES passengers(pass_id),
    CONSTRAINT fk_booking_flight    FOREIGN KEY (flight_id) REFERENCES flights(flight_id) ON DELETE CASCADE
);

-- Payments
CREATE TABLE payments (
    pay_id     INT AUTO_INCREMENT PRIMARY KEY,
    book_id    INT NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    pay_method VARCHAR(50) NOT NULL,
    status     ENUM('Pending', 'Completed', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_payment_booking FOREIGN KEY (book_id) REFERENCES bookings(book_id) ON DELETE CASCADE
);

-- Sample data
INSERT INTO passengers (name, email, phone, password, role) VALUES
('System Admin', 'admin@flightbook.com', '555-0100',
 'password', 'admin');
-- Default admin password: password

INSERT INTO airports (airport_name, city, country) VALUES
('JFK International', 'New York', 'USA'),
('LAX International', 'Los Angeles', 'USA'),
('Heathrow', 'London', 'UK'),
('Dubai International', 'Dubai', 'UAE'),
('Indira Gandhi Intl', 'Delhi', 'India');

INSERT INTO routes (source_id, dest_id, distance) VALUES
(1, 2, 2475),
(1, 3, 3450),
(3, 4, 3400),
(4, 5, 2200),
(2, 5, 8000);

INSERT INTO aircraft (tail_no, model, capacity) VALUES
('N101FB', 'Boeing 737', 180),
('N202FB', 'Airbus A320', 150),
('N303FB', 'Boeing 787', 250);

INSERT INTO flights (route_id, tail_no, dept_time, seats_avail) VALUES
(1, 'N101FB', '2026-06-15 08:00:00', 180),
(2, 'N303FB', '2026-06-16 14:30:00', 250),
(3, 'N202FB', '2026-06-18 10:00:00', 150),
(4, 'N101FB', '2026-06-20 06:45:00', 180),
(5, 'N303FB', '2026-06-22 22:00:00', 250);
