-- ══════════════════════════════════════════════════════════════
--  PuneRooms — Complete Database Setup Script
--  PostgreSQL
--  Run this file once to set up the full schema
--  FIXED: Admin passwords use PHP hash('sha256') format
-- ══════════════════════════════════════════════════════════════

-- Drop existing tables in correct order
DROP TABLE IF EXISTS pr_predictions CASCADE;
DROP TABLE IF EXISTS pr_newsletter CASCADE;
DROP TABLE IF EXISTS pr_reviews CASCADE;
DROP TABLE IF EXISTS pr_saved CASCADE;
DROP TABLE IF EXISTS pr_rooms CASCADE;
DROP TABLE IF EXISTS pr_users CASCADE;

-- ════════════════════════════════════════════
--  TABLE 1: pr_users
-- ════════════════════════════════════════════
CREATE TABLE pr_users (
    id          SERIAL PRIMARY KEY,
    first_name  VARCHAR(100)  NOT NULL,
    last_name   VARCHAR(100),
    email       VARCHAR(200)  UNIQUE NOT NULL,
    phone       VARCHAR(20),
    password    VARCHAR(255)  NOT NULL,
    user_type   VARCHAR(20)   NOT NULL DEFAULT 'student'
                    CHECK (user_type IN ('student', 'owner', 'admin')),
    college     VARCHAR(200),
    is_active   BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP     NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_pr_users_email    ON pr_users (email);
CREATE INDEX idx_pr_users_usertype ON pr_users (user_type);

-- ════════════════════════════════════════════
--  TABLE 2: pr_rooms
-- ════════════════════════════════════════════
CREATE TABLE pr_rooms (
    id                SERIAL PRIMARY KEY,
    user_id           INT REFERENCES pr_users(id) ON DELETE SET NULL,
    title             VARCHAR(300)  NOT NULL,
    description       TEXT,
    monthly_rent      INT           NOT NULL CHECK (monthly_rent > 0),
    security_deposit  INT           NOT NULL DEFAULT 0,
    area              VARCHAR(100)  NOT NULL,
    nearby_college    VARCHAR(200),
    distance_college  INT           NOT NULL DEFAULT 0,
    room_type         VARCHAR(50)   NOT NULL
                          CHECK (room_type IN ('Single Room','Shared Room','PG','1BHK','Studio')),
    gender_preference VARCHAR(20)   NOT NULL
                          CHECK (gender_preference IN ('Male','Female','Co-ed')),
    occupancy         INT           NOT NULL DEFAULT 1,
    status            VARCHAR(20)   NOT NULL DEFAULT 'Available'
                          CHECK (status IN ('Available','Rented')),
    contact           VARCHAR(30)   NOT NULL,
    food_type         VARCHAR(30),
    roommate_pref     VARCHAR(60),
    move_in_date      DATE,
    has_wifi          BOOLEAN NOT NULL DEFAULT FALSE,
    has_ac            BOOLEAN NOT NULL DEFAULT FALSE,
    has_meals         BOOLEAN NOT NULL DEFAULT FALSE,
    has_parking       BOOLEAN NOT NULL DEFAULT FALSE,
    has_laundry       BOOLEAN NOT NULL DEFAULT FALSE,
    has_security      BOOLEAN NOT NULL DEFAULT FALSE,
    has_furnished     BOOLEAN NOT NULL DEFAULT FALSE,
    has_power_backup  BOOLEAN NOT NULL DEFAULT FALSE,
    has_balcony       BOOLEAN NOT NULL DEFAULT FALSE,
    has_cctv          BOOLEAN NOT NULL DEFAULT FALSE,
    has_pets          BOOLEAN NOT NULL DEFAULT FALSE,
    has_water         BOOLEAN NOT NULL DEFAULT FALSE,
    rating            NUMERIC(3,1)  NOT NULL DEFAULT 0.0,
    views             INT           NOT NULL DEFAULT 0,
    upload_date       TIMESTAMP     NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_pr_rooms_status ON pr_rooms (status);
CREATE INDEX idx_pr_rooms_area   ON pr_rooms (area);
CREATE INDEX idx_pr_rooms_type   ON pr_rooms (room_type);
CREATE INDEX idx_pr_rooms_rent   ON pr_rooms (monthly_rent);
CREATE INDEX idx_pr_rooms_user   ON pr_rooms (user_id);
CREATE INDEX idx_pr_rooms_date   ON pr_rooms (upload_date DESC);

-- ════════════════════════════════════════════
--  TABLE 3: pr_saved (favourites)
-- ════════════════════════════════════════════
CREATE TABLE pr_saved (
    id       SERIAL PRIMARY KEY,
    user_id  INT NOT NULL REFERENCES pr_users(id) ON DELETE CASCADE,
    room_id  INT NOT NULL REFERENCES pr_rooms(id) ON DELETE CASCADE,
    saved_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, room_id)
);
CREATE INDEX idx_pr_saved_user ON pr_saved (user_id);
CREATE INDEX idx_pr_saved_room ON pr_saved (room_id);

-- ════════════════════════════════════════════
--  TABLE 4: pr_reviews
-- ════════════════════════════════════════════
CREATE TABLE pr_reviews (
    id         SERIAL PRIMARY KEY,
    room_id    INT NOT NULL REFERENCES pr_rooms(id) ON DELETE CASCADE,
    user_id    INT REFERENCES pr_users(id) ON DELETE SET NULL,
    reviewer   VARCHAR(100),
    rating     INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_txt TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_pr_reviews_room ON pr_reviews (room_id);

-- ════════════════════════════════════════════
--  TABLE 5: pr_newsletter
-- ════════════════════════════════════════════
CREATE TABLE pr_newsletter (
    id            SERIAL PRIMARY KEY,
    email         VARCHAR(200) UNIQUE NOT NULL,
    subscribed_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ════════════════════════════════════════════
--  TABLE 6: pr_predictions
-- ════════════════════════════════════════════
CREATE TABLE pr_predictions (
    id             SERIAL PRIMARY KEY,
    user_id        INT REFERENCES pr_users(id) ON DELETE SET NULL,
    area           VARCHAR(100) NOT NULL,
    room_type      VARCHAR(50)  NOT NULL,
    sqft           INT          NOT NULL DEFAULT 150,
    amenities      TEXT,
    predicted_rent INT          NOT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_pr_predictions_user ON pr_predictions (user_id);

-- ════════════════════════════════════════════
--  ADMIN USERS
--  IMPORTANT: Passwords stored as PHP hash('sha256', 'plaintext')
--  admin123      => 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
--  nikhilpatil184 => computed via: echo -n "nikhilpatil184" | sha256sum
--  owner123      => ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f
--  student123    => 0c2f6fd1d18b16e5a5cf81e0d95d1abd0e54a22b82bc6c3374a3e06e2e55f78a
-- ════════════════════════════════════════════

-- PRIMARY ADMIN (password: admin123)
INSERT INTO pr_users (first_name, last_name, email, phone, password, user_type)
VALUES (
    'Admin', 'PuneRooms',
    'admin@punerooms.com', '0000000000',
    '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9',
    'admin'
);

-- SECONDARY ADMIN - Nikhil (password: nikhilpatil184)
INSERT INTO pr_users (first_name, last_name, email, phone, password, user_type, is_active, created_at)
VALUES (
    'Nikhil', 'Patil',
    'nikhil.patil@punerooms.com', '9876543210',
    '6a8abe8affbe777cb666bd0d0a68c299ee2d84be114d1367ee6ae1e11980bc7a',
    'admin', TRUE, NOW()
);

-- DEMO OWNER (password: owner123)
INSERT INTO pr_users (first_name, last_name, email, phone, password, user_type)
VALUES ('Ramesh', 'Shah', 'owner@demo.com', '9988776655',
        '43a0d17178a9d26c9e0fe9a74b0b45e38d32f27aed887a008a54bf6e033bf7b9', 'owner');

-- DEMO STUDENT (password: student123)
INSERT INTO pr_users (first_name, last_name, email, phone, password, user_type, college)
VALUES ('Priya', 'Joshi', 'student@demo.com', '9123456789',
        '703b0a3d6ad75b649a28adde7d83c6251da457549263bc7ff45ec709b0a8448b', 'student', 'FC College');

-- ════════════════════════════════════════════
--  SAMPLE ROOM DATA
-- ════════════════════════════════════════════
INSERT INTO pr_rooms (title, description, monthly_rent, security_deposit, area, nearby_college, room_type, gender_preference, occupancy, status, contact, has_wifi, has_ac, has_meals, has_parking, has_laundry, has_security, has_furnished, has_power_backup, has_balcony, has_cctv, has_pets, has_water, rating)
VALUES
('Spacious Single Room near FC College','Well-furnished single room with attached bathroom. 5 min walk from Fergusson College.',8000,15000,'Shivaji Nagar','Fergusson College','Single Room','Male',1,'Available','9876543210',TRUE,FALSE,TRUE,FALSE,TRUE,FALSE,TRUE,FALSE,FALSE,FALSE,FALSE,TRUE,4.2),
('Girls PG near Pune University','Safe PG for girls with 24/7 security, CCTV and home-cooked meals.',12000,20000,'Aundh','Pune University','PG','Female',2,'Available','8765432109',TRUE,TRUE,TRUE,FALSE,TRUE,TRUE,FALSE,FALSE,FALSE,TRUE,FALSE,TRUE,4.7),
('Affordable Shared Room in Kothrud','Budget-friendly shared room. High-speed WiFi and common kitchen.',5500,10000,'Kothrud','BMCC College','Shared Room','Co-ed',2,'Available','7654321098',TRUE,FALSE,FALSE,TRUE,FALSE,FALSE,TRUE,FALSE,FALSE,FALSE,FALSE,FALSE,3.8),
('Modern Studio Apartment in Baner','Fully furnished studio with private kitchenette, AC and WiFi.',15000,25000,'Baner','MIT College','Studio','Co-ed',1,'Available','6543210987',TRUE,TRUE,FALSE,TRUE,FALSE,FALSE,TRUE,TRUE,TRUE,FALSE,FALSE,TRUE,4.5),
('Premium 1BHK in Koregaon Park','Luxurious 1BHK with full furnishing, power backup, and parking.',20000,35000,'Koregaon Park','Symbiosis College','1BHK','Co-ed',1,'Available','4321098765',TRUE,TRUE,FALSE,TRUE,FALSE,TRUE,TRUE,TRUE,TRUE,TRUE,FALSE,TRUE,4.9),
('Girls PG in Wakad near IT Park','Premium PG for women with AC, meals, laundry and security.',13500,25000,'Wakad','PCCOER','PG','Female',2,'Available','8765498765',TRUE,TRUE,TRUE,FALSE,TRUE,TRUE,FALSE,TRUE,FALSE,TRUE,FALSE,TRUE,4.6);
