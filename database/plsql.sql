-- Flight Booking & Management System - MySQL Stored Logic
-- Run after schema.sql: mysql -u root -p flightbook1 < database/plsql.sql

USE flightbook1;

DROP TRIGGER IF EXISTS Update_Seats;
DROP TRIGGER IF EXISTS Restore_Seats;
DROP PROCEDURE IF EXISTS Process_Booking;
DROP PROCEDURE IF EXISTS Cancel_Booking;
DROP FUNCTION IF EXISTS Calculate_Fare;
