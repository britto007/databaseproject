

BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Get_Admin_Stats'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Get_Flight_Occupancy'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Get_Popular_Routes'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Get_Revenue_By_Month'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP FUNCTION Fn_Flight_Occupancy'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP FUNCTION Fn_Popular_Routes'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP FUNCTION Fn_Revenue_By_Month'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TYPE t_occupancy_tab FORCE'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TYPE t_occupancy_row FORCE'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TYPE t_popular_routes_tab FORCE'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TYPE t_popular_routes_row FORCE'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TYPE t_revenue_tab FORCE'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TYPE t_revenue_row FORCE'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Update_Flight'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Delete_Aircraft'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Update_Aircraft'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Add_Aircraft'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Delete_Route'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Update_Route'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Add_Route'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Delete_Airport'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Update_Airport'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Add_Airport'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Delete_Flight'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP PROCEDURE Add_Flight'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP FUNCTION Calculate_Fare'; EXCEPTION WHEN OTHERS THEN NULL; END;
/



CREATE OR REPLACE FUNCTION Calculate_Fare(
    p_route_id  IN NUMBER,
    p_surcharge IN NUMBER DEFAULT NULL
) RETURN NUMBER AS
    v_distance NUMBER;
    v_surcharge NUMBER := NVL(p_surcharge, 50);
BEGIN
    SELECT distance
    INTO v_distance
    FROM routes
    WHERE route_id = p_route_id;

    RETURN (v_distance * 0.10) + v_surcharge;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20006, 'Invalid route ID.');
END Calculate_Fare;
/



CREATE OR REPLACE PROCEDURE Add_Airport(
    p_name    IN VARCHAR2,
    p_city    IN VARCHAR2,
    p_country IN VARCHAR2,
    p_airp_id OUT NUMBER
) AS
BEGIN
    IF TRIM(p_name) IS NULL OR TRIM(p_city) IS NULL OR TRIM(p_country) IS NULL THEN
        RAISE_APPLICATION_ERROR(-20010, 'Airport name, city, and country are required.');
    END IF;

    INSERT INTO airports (airport_name, city, country)
    VALUES (TRIM(p_name), TRIM(p_city), TRIM(p_country))
    RETURNING airp_id INTO p_airp_id;
END Add_Airport;
/

CREATE OR REPLACE PROCEDURE Update_Airport(
    p_airp_id IN NUMBER,
    p_name    IN VARCHAR2,
    p_city    IN VARCHAR2,
    p_country IN VARCHAR2
) AS
    v_cnt NUMBER;
BEGIN
    IF TRIM(p_name) IS NULL OR TRIM(p_city) IS NULL OR TRIM(p_country) IS NULL THEN
        RAISE_APPLICATION_ERROR(-20010, 'Airport name, city, and country are required.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM airports
    WHERE airp_id = p_airp_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20011, 'Airport does not exist.');
    END IF;

    UPDATE airports
    SET airport_name = TRIM(p_name),
        city         = TRIM(p_city),
        country      = TRIM(p_country)
    WHERE airp_id = p_airp_id;
END Update_Airport;
/

CREATE OR REPLACE PROCEDURE Delete_Airport(
    p_airp_id IN NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_cnt
    FROM airports
    WHERE airp_id = p_airp_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20011, 'Airport does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM routes
    WHERE source_id = p_airp_id OR dest_id = p_airp_id;

    IF v_cnt > 0 THEN
        RAISE_APPLICATION_ERROR(-20012, 'Cannot delete airport used by existing routes.');
    END IF;

    DELETE FROM airports
    WHERE airp_id = p_airp_id;
END Delete_Airport;
/



CREATE OR REPLACE PROCEDURE Add_Route(
    p_source_id IN NUMBER,
    p_dest_id   IN NUMBER,
    p_distance  IN NUMBER,
    p_route_id  OUT NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    IF p_source_id <= 0 OR p_dest_id <= 0 THEN
        RAISE_APPLICATION_ERROR(-20017, 'Source and destination airports are required.');
    END IF;

    IF p_source_id = p_dest_id THEN
        RAISE_APPLICATION_ERROR(-20015, 'Source and destination must be different.');
    END IF;

    IF p_distance <= 0 THEN
        RAISE_APPLICATION_ERROR(-20016, 'Distance must be greater than zero.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM airports
    WHERE airp_id IN (p_source_id, p_dest_id);

    IF v_cnt < 2 THEN
        RAISE_APPLICATION_ERROR(-20017, 'Invalid source or destination airport.');
    END IF;

    INSERT INTO routes (source_id, dest_id, distance)
    VALUES (p_source_id, p_dest_id, p_distance)
    RETURNING route_id INTO p_route_id;
END Add_Route;
/

CREATE OR REPLACE PROCEDURE Update_Route(
    p_route_id  IN NUMBER,
    p_source_id IN NUMBER,
    p_dest_id   IN NUMBER,
    p_distance  IN NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_cnt
    FROM routes
    WHERE route_id = p_route_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20013, 'Route does not exist.');
    END IF;

    IF p_source_id <= 0 OR p_dest_id <= 0 THEN
        RAISE_APPLICATION_ERROR(-20017, 'Source and destination airports are required.');
    END IF;

    IF p_source_id = p_dest_id THEN
        RAISE_APPLICATION_ERROR(-20015, 'Source and destination must be different.');
    END IF;

    IF p_distance <= 0 THEN
        RAISE_APPLICATION_ERROR(-20016, 'Distance must be greater than zero.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM airports
    WHERE airp_id IN (p_source_id, p_dest_id);

    IF v_cnt < 2 THEN
        RAISE_APPLICATION_ERROR(-20017, 'Invalid source or destination airport.');
    END IF;

    UPDATE routes
    SET source_id = p_source_id,
        dest_id   = p_dest_id,
        distance  = p_distance
    WHERE route_id = p_route_id;
END Update_Route;
/

CREATE OR REPLACE PROCEDURE Delete_Route(
    p_route_id IN NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_cnt
    FROM routes
    WHERE route_id = p_route_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20013, 'Route does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM flights
    WHERE route_id = p_route_id;

    IF v_cnt > 0 THEN
        RAISE_APPLICATION_ERROR(-20014, 'Cannot delete route used by existing flights.');
    END IF;

    DELETE FROM routes
    WHERE route_id = p_route_id;
END Delete_Route;
/



CREATE OR REPLACE PROCEDURE Add_Aircraft(
    p_tail_no  IN VARCHAR2,
    p_model    IN VARCHAR2,
    p_capacity IN NUMBER
) AS
BEGIN
    IF TRIM(p_tail_no) IS NULL OR TRIM(p_model) IS NULL THEN
        RAISE_APPLICATION_ERROR(-20018, 'Tail number and model are required.');
    END IF;

    IF p_capacity <= 0 THEN
        RAISE_APPLICATION_ERROR(-20018, 'Capacity must be greater than zero.');
    END IF;

    INSERT INTO aircraft (tail_no, model, capacity)
    VALUES (TRIM(p_tail_no), TRIM(p_model), p_capacity);
END Add_Aircraft;
/

CREATE OR REPLACE PROCEDURE Update_Aircraft(
    p_tail_no  IN VARCHAR2,
    p_model    IN VARCHAR2,
    p_capacity IN NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    IF TRIM(p_model) IS NULL THEN
        RAISE_APPLICATION_ERROR(-20018, 'Model is required.');
    END IF;

    IF p_capacity <= 0 THEN
        RAISE_APPLICATION_ERROR(-20018, 'Capacity must be greater than zero.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM aircraft
    WHERE tail_no = p_tail_no;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20019, 'Aircraft does not exist.');
    END IF;

    UPDATE aircraft
    SET model    = TRIM(p_model),
        capacity = p_capacity
    WHERE tail_no = p_tail_no;
END Update_Aircraft;
/

CREATE OR REPLACE PROCEDURE Delete_Aircraft(
    p_tail_no IN VARCHAR2
) AS
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_cnt
    FROM aircraft
    WHERE tail_no = p_tail_no;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20019, 'Aircraft does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM flights
    WHERE tail_no = p_tail_no;

    IF v_cnt > 0 THEN
        RAISE_APPLICATION_ERROR(-20020, 'Cannot delete aircraft assigned to flights.');
    END IF;

    DELETE FROM aircraft
    WHERE tail_no = p_tail_no;
END Delete_Aircraft;
/



CREATE OR REPLACE PROCEDURE Add_Flight(
    p_route_id    IN NUMBER,
    p_tail_no     IN VARCHAR2,
    p_dept_time   IN TIMESTAMP,
    p_seats_avail IN NUMBER,
    p_flight_id   OUT NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_cnt
    FROM routes
    WHERE route_id = p_route_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Invalid route ID. Route does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM aircraft
    WHERE tail_no = p_tail_no;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20002, 'Invalid aircraft tail number. Aircraft does not exist.');
    END IF;

    IF p_seats_avail < 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'Seats available cannot be negative.');
    END IF;

    INSERT INTO flights (route_id, tail_no, dept_time, seats_avail)
    VALUES (p_route_id, p_tail_no, p_dept_time, p_seats_avail)
    RETURNING flight_id INTO p_flight_id;
END Add_Flight;
/

CREATE OR REPLACE PROCEDURE Update_Flight(
    p_flight_id   IN NUMBER,
    p_route_id    IN NUMBER,
    p_tail_no     IN VARCHAR2,
    p_dept_time   IN TIMESTAMP,
    p_seats_avail IN NUMBER
) AS
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_cnt
    FROM flights
    WHERE flight_id = p_flight_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20004, 'Flight does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM routes
    WHERE route_id = p_route_id;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Invalid route ID. Route does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_cnt
    FROM aircraft
    WHERE tail_no = p_tail_no;

    IF v_cnt = 0 THEN
        RAISE_APPLICATION_ERROR(-20002, 'Invalid aircraft tail number. Aircraft does not exist.');
    END IF;

    IF p_seats_avail < 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'Seats available cannot be negative.');
    END IF;

    UPDATE flights
    SET route_id    = p_route_id,
        tail_no     = p_tail_no,
        dept_time   = p_dept_time,
        seats_avail = p_seats_avail
    WHERE flight_id = p_flight_id;
END Update_Flight;
/

CREATE OR REPLACE PROCEDURE Delete_Flight(
    p_flight_id IN NUMBER
) AS
    v_exists NUMBER;
    v_confirmed NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_exists
    FROM flights
    WHERE flight_id = p_flight_id;

    IF v_exists = 0 THEN
        RAISE_APPLICATION_ERROR(-20004, 'Flight does not exist.');
    END IF;

    SELECT COUNT(*)
    INTO v_confirmed
    FROM bookings
    WHERE flight_id = p_flight_id
      AND status = 'Confirmed';

    IF v_confirmed > 0 THEN
        RAISE_APPLICATION_ERROR(-20005, 'Cannot delete flight with active bookings.');
    END IF;

    DELETE FROM flights
    WHERE flight_id = p_flight_id;
END Delete_Flight;
/



CREATE OR REPLACE PROCEDURE Get_Admin_Stats(
    p_airports OUT NUMBER,
    p_routes   OUT NUMBER,
    p_aircraft OUT NUMBER,
    p_flights  OUT NUMBER,
    p_bookings OUT NUMBER
) AS
BEGIN
    SELECT COUNT(*) INTO p_airports FROM airports;
    SELECT COUNT(*) INTO p_routes FROM routes;
    SELECT COUNT(*) INTO p_aircraft FROM aircraft;
    SELECT COUNT(*) INTO p_flights FROM flights;
    SELECT COUNT(*)
    INTO p_bookings
    FROM bookings
    WHERE status = 'Confirmed';
END Get_Admin_Stats;
/














































CREATE OR REPLACE TYPE t_revenue_row AS OBJECT (
    month_label     VARCHAR2(7),
    total_revenue   NUMBER,
    payment_count   NUMBER
);
/

CREATE OR REPLACE TYPE t_revenue_tab AS TABLE OF t_revenue_row;
/

CREATE OR REPLACE FUNCTION Fn_Revenue_By_Month RETURN t_revenue_tab PIPELINED AS
BEGIN
    FOR rec IN (
        SELECT TO_CHAR(f.dept_time, 'YYYY-MM') AS month_label,
               SUM(p.amount) AS total_revenue,
               COUNT(p.pay_id) AS payment_count
        FROM payments p
        JOIN bookings b ON p.book_id = b.book_id
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE p.status = 'Completed'
        GROUP BY TO_CHAR(f.dept_time, 'YYYY-MM')
        ORDER BY month_label DESC
    ) LOOP
        PIPE ROW(t_revenue_row(rec.month_label, rec.total_revenue, rec.payment_count));
    END LOOP;

    RETURN;
END Fn_Revenue_By_Month;
/

CREATE OR REPLACE TYPE t_popular_routes_row AS OBJECT (
    route_id       NUMBER,
    source_city    VARCHAR2(100),
    dest_city      VARCHAR2(100),
    booking_count  NUMBER
);
/

CREATE OR REPLACE TYPE t_popular_routes_tab AS TABLE OF t_popular_routes_row;
/

CREATE OR REPLACE FUNCTION Fn_Popular_Routes RETURN t_popular_routes_tab PIPELINED AS
BEGIN
    FOR rec IN (
        SELECT r.route_id,
               src.city AS source_city,
               dst.city AS dest_city,
               COUNT(b.book_id) AS booking_count
        FROM bookings b
        JOIN flights f ON b.flight_id = f.flight_id
        JOIN routes r ON f.route_id = r.route_id
        JOIN airports src ON r.source_id = src.airp_id
        JOIN airports dst ON r.dest_id = dst.airp_id
        WHERE b.status = 'Confirmed'
        GROUP BY r.route_id, src.city, dst.city
        ORDER BY booking_count DESC, src.city
    ) LOOP
        PIPE ROW(t_popular_routes_row(rec.route_id, rec.source_city, rec.dest_city, rec.booking_count));
    END LOOP;

    RETURN;
END Fn_Popular_Routes;
/

CREATE OR REPLACE TYPE t_occupancy_row AS OBJECT (
    flight_id      NUMBER,
    source_city    VARCHAR2(100),
    dest_city      VARCHAR2(100),
    dept_time      TIMESTAMP,
    capacity       NUMBER,
    seats_avail    NUMBER,
    seats_booked   NUMBER,
    occupancy_pct  NUMBER
);
/

CREATE OR REPLACE TYPE t_occupancy_tab AS TABLE OF t_occupancy_row;
/

CREATE OR REPLACE FUNCTION Fn_Flight_Occupancy RETURN t_occupancy_tab PIPELINED AS
BEGIN
    FOR rec IN (
        SELECT f.flight_id,
               src.city AS source_city,
               dst.city AS dest_city,
               f.dept_time,
               a.capacity,
               f.seats_avail,
               (a.capacity - f.seats_avail) AS seats_booked,
               CASE
                   WHEN a.capacity > 0 THEN ROUND((a.capacity - f.seats_avail) / a.capacity * 100, 1)
                   ELSE 0
               END AS occupancy_pct
        FROM flights f
        JOIN routes r ON f.route_id = r.route_id
        JOIN airports src ON r.source_id = src.airp_id
        JOIN airports dst ON r.dest_id = dst.airp_id
        JOIN aircraft a ON f.tail_no = a.tail_no
        ORDER BY occupancy_pct DESC, f.dept_time DESC
    ) LOOP
        PIPE ROW(t_occupancy_row(
            rec.flight_id, rec.source_city, rec.dest_city, rec.dept_time,
            rec.capacity, rec.seats_avail, rec.seats_booked, rec.occupancy_pct
        ));
    END LOOP;

    RETURN;
END Fn_Flight_Occupancy;
/
