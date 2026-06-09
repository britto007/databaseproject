Technical Specification: Flight Booking & Management System
1. Project Goal
Build a database-driven web application for flight reservations using Vanilla PHP and PL/SQL. The system must handle two roles: Passengers (Booking/History) and Administrators (Flight/Route Management).

2. Tech Stack & Environment
Backend: PHP
Database: MySQL
Frontend: HTml,css,js
Organization: Modular structure (separate logic from UI).
3. Database Schema (DDL)
Generate the following tables with appropriate constraints:

passengers (pass_id PK, name, email UNIQUE, phone, password)
airports (airp_id PK, airport_name, city, country)
routes (route_id PK, source_id FK, dest_id FK, distance)
aircraft (tail_no PK, model, capacity)
flights (flight_id PK, route_id FK, tail_no FK, dept_time, seats_avail)
bookings (book_id PK, pass_id FK, flight_id FK, seat_no, status, fare)
payments (pay_id PK, book_id FK, amount, pay_method, status)
4. MySQL Logic Requirements
Implement the following as stored procedures or triggers in the database:

Procedure Process_Booking: Check seats_avail, insert booking, and update flight seats.
Function Calculate_Fare: Logic: (distance * 0.10) + surcharge.
Trigger Update_Seats: AFTER INSERT on bookings to decrement flights.seats_avail.
Trigger Restore_Seats: AFTER UPDATE to increment seats if status becomes 'Cancelled'.
5. Application Pages to Build
Shared
Login/Register: Secure authentication with password hashing.
Dashboard: Role-based redirection.
Passenger Flow
Search Flights: Filter by source/destination airports and date.
Booking Page: Select flight, confirm details, and process payment.
My Trips: List of all bookings with a "Cancel" button.
Admin Flow
Flight Management: Create/Edit/Delete flights.
Reports:
Total revenue by month.
Most popular routes (by booking count).
Flight occupancy percentage.
6. Coding Rules for Cursor
Security: Use prepared statements for all SQL queries.
Error Handling: Use Try-Catch blocks for database operations.
Styling: Keep the UI clean, professional, and responsive.
Validation: Validate all user inputs on both client and server sides.