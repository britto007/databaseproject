-- Flight Booking & Management System - MySQL Stored Logic
-- Run after schema.sql: mysql -u root -p flightbook1 < database/plsql.sql

USE flightbook1;

DROP PROCEDURE IF EXISTS Add_Flight;
DROP PROCEDURE IF EXISTS Delete_Flight;

DELIMITER //

-- Add Flight Stored Procedure
CREATE PROCEDURE Add_Flight(
    IN p_route_id INT,
    IN p_tail_no VARCHAR(20),
    IN p_dept_time DATETIME,
    IN p_seats_avail INT,
    OUT p_flight_id INT
)
BEGIN
    -- Validate Route
    IF NOT EXISTS (SELECT 1 FROM routes WHERE route_id = p_route_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid route ID. Route does not exist.';
    END IF;

    -- Validate Aircraft
    IF NOT EXISTS (SELECT 1 FROM aircraft WHERE tail_no = p_tail_no) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid aircraft tail number. Aircraft does not exist.';
    END IF;

    -- Validate Seats
    IF p_seats_avail < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Seats available cannot be negative.';
    END IF;

    -- Insert flight
    INSERT INTO flights (route_id, tail_no, dept_time, seats_avail)
    VALUES (p_route_id, p_tail_no, p_dept_time, p_seats_avail);

    -- Set output ID
    SET p_flight_id = LAST_INSERT_ID();
END //

-- Delete Flight Stored Procedure
CREATE PROCEDURE Delete_Flight(
    IN p_flight_id INT
)
BEGIN
    DECLARE v_confirmed_bookings INT DEFAULT 0;

    -- Check if flight exists
    IF NOT EXISTS (SELECT 1 FROM flights WHERE flight_id = p_flight_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Flight does not exist.';
    END IF;

    -- Check for active bookings
    SELECT COUNT(*) INTO v_confirmed_bookings
    FROM bookings
    WHERE flight_id = p_flight_id AND status = 'Confirmed';

    IF v_confirmed_bookings > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete flight with active bookings.';
    END IF;

    -- Delete flight (cascades to delete/cancel bookings and payments if database constraints are set to CASCADE)
    DELETE FROM flights WHERE flight_id = p_flight_id;
END //

DELIMITER ;


