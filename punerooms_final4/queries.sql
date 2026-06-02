-- ══════════════════════════════════════════════════════════════
--  PuneRooms — 30 Useful SQL Queries (Reference File)
--  All queries are commented out. Run individually as needed.
--  Database: PostgreSQL
-- ══════════════════════════════════════════════════════════════


-- ────────────────────────────────────────────
--  1. DASHBOARD SUMMARY METRICS
-- ────────────────────────────────────────────
/*
SELECT 'Total Rooms'      AS metric, COUNT(*)::text AS value FROM pr_rooms
UNION ALL
SELECT 'Available Rooms',  COUNT(*)::text FROM pr_rooms WHERE status = 'Available'
UNION ALL
SELECT 'Rented Rooms',     COUNT(*)::text FROM pr_rooms WHERE status = 'Rented'
UNION ALL
SELECT 'Total Users',      COUNT(*)::text FROM pr_users
UNION ALL
SELECT 'Total Students',   COUNT(*)::text FROM pr_users WHERE user_type = 'student'
UNION ALL
SELECT 'Total Owners',     COUNT(*)::text FROM pr_users WHERE user_type = 'owner'
UNION ALL
SELECT 'Avg Rent (Rs)',    COALESCE(ROUND(AVG(monthly_rent)), 0)::text FROM pr_rooms WHERE status = 'Available';
*/


-- ────────────────────────────────────────────
--  2. ALL REGISTERED USERS
-- ────────────────────────────────────────────
/*
SELECT
    id,
    first_name || ' ' || COALESCE(last_name, '') AS full_name,
    email,
    phone,
    user_type,
    college,
    is_active,
    TO_CHAR(created_at, 'DD Mon YYYY HH:MI AM') AS joined
FROM pr_users
ORDER BY created_at DESC;
*/


-- ────────────────────────────────────────────
--  3. ALL ROOM LISTINGS
-- ────────────────────────────────────────────
/*
SELECT
    id,
    title,
    area,
    room_type,
    gender_preference,
    monthly_rent,
    security_deposit,
    status,
    contact,
    TO_CHAR(upload_date, 'DD Mon YYYY') AS listed_on
FROM pr_rooms
ORDER BY upload_date DESC;
*/


-- ────────────────────────────────────────────
--  4. AVAILABLE ROOMS ONLY (sorted by rent)
-- ────────────────────────────────────────────
/*
SELECT
    id, title, area, room_type, monthly_rent, gender_preference, nearby_college
FROM pr_rooms
WHERE status = 'Available'
ORDER BY monthly_rent ASC;
*/


-- ────────────────────────────────────────────
--  5. ROOMS WITH OWNER DETAILS (JOIN)
-- ────────────────────────────────────────────
/*
SELECT
    r.id,
    r.title,
    r.area,
    r.monthly_rent,
    r.status,
    u.first_name || ' ' || COALESCE(u.last_name, '') AS owner_name,
    u.email AS owner_email,
    u.phone AS owner_phone
FROM pr_rooms r
LEFT JOIN pr_users u ON r.user_id = u.id
ORDER BY r.upload_date DESC;
*/


-- ────────────────────────────────────────────
--  6. ROOM TYPE BREAKDOWN WITH AVAILABILITY
-- ────────────────────────────────────────────
/*
SELECT
    room_type,
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN status = 'Rented'    THEN 1 ELSE 0 END) AS rented,
    ROUND(AVG(monthly_rent)) AS avg_rent
FROM pr_rooms
GROUP BY room_type
ORDER BY total DESC;
*/


-- ────────────────────────────────────────────
--  7. AREA-WISE ROOM STATISTICS
-- ────────────────────────────────────────────
/*
SELECT
    area,
    COUNT(*) AS total_rooms,
    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) AS available_now,
    ROUND(AVG(monthly_rent)) AS avg_monthly_rent,
    MIN(monthly_rent) AS min_rent,
    MAX(monthly_rent) AS max_rent
FROM pr_rooms
GROUP BY area
ORDER BY total_rooms DESC;
*/


-- ────────────────────────────────────────────
--  8. GENDER PREFERENCE DISTRIBUTION
-- ────────────────────────────────────────────
/*
SELECT
    gender_preference,
    COUNT(*) AS total_rooms,
    ROUND(AVG(monthly_rent)) AS avg_rent
FROM pr_rooms
GROUP BY gender_preference;
*/


-- ────────────────────────────────────────────
--  9. RENT RANGE DISTRIBUTION (AVAILABLE ROOMS)
-- ────────────────────────────────────────────
/*
SELECT
    CASE
        WHEN monthly_rent < 5000                    THEN 'Under Rs5K'
        WHEN monthly_rent BETWEEN 5000 AND 10000    THEN 'Rs5K - Rs10K'
        WHEN monthly_rent BETWEEN 10001 AND 15000   THEN 'Rs10K - Rs15K'
        ELSE 'Above Rs15K'
    END AS rent_range,
    COUNT(*) AS rooms,
    ROUND(AVG(monthly_rent)) AS avg_in_range
FROM pr_rooms
WHERE status = 'Available'
GROUP BY
    CASE
        WHEN monthly_rent < 5000                    THEN 'Under Rs5K'
        WHEN monthly_rent BETWEEN 5000 AND 10000    THEN 'Rs5K - Rs10K'
        WHEN monthly_rent BETWEEN 10001 AND 15000   THEN 'Rs10K - Rs15K'
        ELSE 'Above Rs15K'
    END
ORDER BY MIN(monthly_rent);
*/


-- ────────────────────────────────────────────
--  10. AMENITY POPULARITY (how many rooms have each)
-- ────────────────────────────────────────────
/*
SELECT 'WiFi'         AS amenity, COUNT(*) AS rooms_with FROM pr_rooms WHERE has_wifi = TRUE
UNION ALL
SELECT 'AC',           COUNT(*) FROM pr_rooms WHERE has_ac = TRUE
UNION ALL
SELECT 'Meals',        COUNT(*) FROM pr_rooms WHERE has_meals = TRUE
UNION ALL
SELECT 'Furnished',    COUNT(*) FROM pr_rooms WHERE has_furnished = TRUE
UNION ALL
SELECT 'Parking',      COUNT(*) FROM pr_rooms WHERE has_parking = TRUE
UNION ALL
SELECT 'Laundry',      COUNT(*) FROM pr_rooms WHERE has_laundry = TRUE
UNION ALL
SELECT 'Security',     COUNT(*) FROM pr_rooms WHERE has_security = TRUE
UNION ALL
SELECT 'Power Backup', COUNT(*) FROM pr_rooms WHERE has_power_backup = TRUE
UNION ALL
SELECT 'CCTV',         COUNT(*) FROM pr_rooms WHERE has_cctv = TRUE
UNION ALL
SELECT 'Water 24/7',   COUNT(*) FROM pr_rooms WHERE has_water = TRUE
ORDER BY rooms_with DESC;
*/


-- ────────────────────────────────────────────
--  11. RECENTLY ADDED ROOMS (last 5)
-- ────────────────────────────────────────────
/*
SELECT
    id, title, monthly_rent, area, status,
    TO_CHAR(upload_date, 'DD Mon HH:MI AM') AS uploaded
FROM pr_rooms
ORDER BY upload_date DESC
LIMIT 5;
*/


-- ────────────────────────────────────────────
--  12. RECENTLY REGISTERED USERS (last 5)
-- ────────────────────────────────────────────
/*
SELECT
    first_name || ' ' || COALESCE(last_name, '') AS name,
    email,
    user_type,
    TO_CHAR(created_at, 'DD Mon HH:MI AM') AS joined
FROM pr_users
ORDER BY created_at DESC
LIMIT 5;
*/


-- ────────────────────────────────────────────
--  13. USER TYPE DISTRIBUTION WITH PERCENTAGE
-- ────────────────────────────────────────────
/*
SELECT
    user_type,
    COUNT(*) AS total,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM pr_users), 1) AS percentage
FROM pr_users
GROUP BY user_type;
*/


-- ────────────────────────────────────────────
--  14. ALL SAVED/FAVOURITE ROOMS
-- ────────────────────────────────────────────
/*
SELECT
    u.first_name || ' ' || COALESCE(u.last_name,'') AS student_name,
    r.title AS room_title,
    r.area,
    r.monthly_rent,
    TO_CHAR(s.saved_at, 'DD Mon YYYY') AS saved_on
FROM pr_saved s
JOIN pr_users u ON s.user_id = u.id
JOIN pr_rooms r ON s.room_id = r.id
ORDER BY s.saved_at DESC;
*/


-- ────────────────────────────────────────────
--  15. MOST SAVED ROOMS (popularity ranking)
-- ────────────────────────────────────────────
/*
SELECT
    r.id, r.title, r.area, r.monthly_rent,
    COUNT(s.id) AS times_saved
FROM pr_rooms r
LEFT JOIN pr_saved s ON r.id = s.room_id
GROUP BY r.id, r.title, r.area, r.monthly_rent
ORDER BY times_saved DESC
LIMIT 10;
*/


-- ────────────────────────────────────────────
--  16. ROOMS NEAR SPECIFIC COLLEGE
-- ────────────────────────────────────────────
/*
SELECT
    id, title, area, monthly_rent, room_type, gender_preference, status
FROM pr_rooms
WHERE nearby_college ILIKE '%FC College%'
   OR nearby_college ILIKE '%Fergusson%'
ORDER BY monthly_rent ASC;
*/


-- ────────────────────────────────────────────
--  17. HIGH-VALUE ROOMS (above average rent)
-- ────────────────────────────────────────────
/*
SELECT
    id, title, area, monthly_rent, room_type, status
FROM pr_rooms
WHERE monthly_rent > (SELECT AVG(monthly_rent) FROM pr_rooms)
ORDER BY monthly_rent DESC;
*/


-- ────────────────────────────────────────────
--  18. BUDGET ROOMS (under Rs 6000)
-- ────────────────────────────────────────────
/*
SELECT
    id, title, area, monthly_rent, room_type, gender_preference
FROM pr_rooms
WHERE monthly_rent <= 6000 AND status = 'Available'
ORDER BY monthly_rent ASC;
*/


-- ────────────────────────────────────────────
--  19. ROOMS LISTED PER MONTH (last 6 months)
-- ────────────────────────────────────────────
/*
SELECT
    TO_CHAR(DATE_TRUNC('month', upload_date), 'Mon YYYY') AS month,
    COUNT(*) AS new_listings
FROM pr_rooms
WHERE upload_date >= CURRENT_DATE - INTERVAL '6 months'
GROUP BY DATE_TRUNC('month', upload_date)
ORDER BY DATE_TRUNC('month', upload_date) DESC;
*/


-- ────────────────────────────────────────────
--  20. OWNERS AND THEIR ROOM COUNTS
-- ────────────────────────────────────────────
/*
SELECT
    u.id,
    u.first_name || ' ' || COALESCE(u.last_name,'') AS owner_name,
    u.email,
    COUNT(r.id) AS rooms_listed,
    SUM(CASE WHEN r.status='Available' THEN 1 ELSE 0 END) AS available
FROM pr_users u
LEFT JOIN pr_rooms r ON u.id = r.user_id
WHERE u.user_type = 'owner'
GROUP BY u.id, u.first_name, u.last_name, u.email
ORDER BY rooms_listed DESC;
*/


-- ────────────────────────────────────────────
--  21. ALL ADMIN USERS
-- ────────────────────────────────────────────
/*
SELECT
    id, first_name, last_name, email, phone, created_at
FROM pr_users
WHERE user_type = 'admin'
ORDER BY id;
*/


-- ────────────────────────────────────────────
--  22. INACTIVE USERS
-- ────────────────────────────────────────────
/*
SELECT
    id,
    first_name || ' ' || COALESCE(last_name,'') AS name,
    email, user_type
FROM pr_users
WHERE is_active = FALSE;
*/


-- ────────────────────────────────────────────
--  23. ALL RENT PREDICTIONS
-- ────────────────────────────────────────────
/*
SELECT
    p.id,
    u.first_name AS user,
    p.area,
    p.room_type,
    p.sqft,
    p.predicted_rent,
    TO_CHAR(p.created_at, 'DD Mon YYYY HH:MI AM') AS predicted_on
FROM pr_predictions p
LEFT JOIN pr_users u ON p.user_id = u.id
ORDER BY p.created_at DESC;
*/


-- ────────────────────────────────────────────
--  24. PREDICTION STATS BY AREA
-- ────────────────────────────────────────────
/*
SELECT
    area,
    COUNT(*) AS predictions,
    ROUND(AVG(predicted_rent)) AS avg_predicted_rent,
    MIN(predicted_rent) AS min_prediction,
    MAX(predicted_rent) AS max_prediction
FROM pr_predictions
GROUP BY area
ORDER BY predictions DESC;
*/


-- ────────────────────────────────────────────
--  25. FULLY AMENITIZED ROOMS (WiFi + AC + Meals + Furnished)
-- ────────────────────────────────────────────
/*
SELECT
    id, title, area, monthly_rent, room_type, gender_preference
FROM pr_rooms
WHERE has_wifi = TRUE
  AND has_ac = TRUE
  AND has_meals = TRUE
  AND has_furnished = TRUE
  AND status = 'Available'
ORDER BY monthly_rent ASC;
*/


-- ────────────────────────────────────────────
--  26. ROOMS WITH TOP RATINGS
-- ────────────────────────────────────────────
/*
SELECT
    id, title, area, monthly_rent, room_type, rating
FROM pr_rooms
WHERE rating >= 4.5
ORDER BY rating DESC;
*/


-- ────────────────────────────────────────────
--  27. NEWSLETTER SUBSCRIBERS
-- ────────────────────────────────────────────
/*
SELECT
    email,
    TO_CHAR(subscribed_at, 'DD Mon YYYY') AS subscribed_on
FROM pr_newsletter
ORDER BY subscribed_at DESC;
*/


-- ────────────────────────────────────────────
--  28. USER ACTIVITY: who has saved rooms
-- ────────────────────────────────────────────
/*
SELECT
    u.first_name || ' ' || COALESCE(u.last_name,'') AS name,
    u.email,
    COUNT(s.id) AS rooms_saved
FROM pr_users u
LEFT JOIN pr_saved s ON u.id = s.user_id
GROUP BY u.id, u.first_name, u.last_name, u.email
HAVING COUNT(s.id) > 0
ORDER BY rooms_saved DESC;
*/


-- ────────────────────────────────────────────
--  29. COLLEGE-WISE ROOM DEMAND
-- ────────────────────────────────────────────
/*
SELECT
    nearby_college,
    COUNT(*) AS rooms_nearby,
    ROUND(AVG(monthly_rent)) AS avg_rent,
    SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) AS available
FROM pr_rooms
WHERE nearby_college IS NOT NULL AND nearby_college != ''
GROUP BY nearby_college
ORDER BY rooms_nearby DESC
LIMIT 10;
*/


-- ────────────────────────────────────────────
--  30. COMPLETE REPORT: room + owner + saved count
-- ────────────────────────────────────────────
/*
SELECT
    r.id,
    r.title,
    r.area,
    r.room_type,
    r.monthly_rent,
    r.status,
    u.first_name || ' ' || COALESCE(u.last_name,'') AS owner,
    COUNT(s.id) AS total_saves,
    r.rating,
    TO_CHAR(r.upload_date, 'DD Mon YYYY') AS listed_on
FROM pr_rooms r
LEFT JOIN pr_users u ON r.user_id = u.id
LEFT JOIN pr_saved s ON r.id = s.room_id
GROUP BY r.id, r.title, r.area, r.room_type, r.monthly_rent,
         r.status, u.first_name, u.last_name, r.rating, r.upload_date
ORDER BY total_saves DESC, r.upload_date DESC;
*/
