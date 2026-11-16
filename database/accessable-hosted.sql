-- =====================================================================
-- AccessAble — Unified Database Schema
-- Owner: Member 4 (Reviews, Authentication & Database)
--
-- Import with phpMyAdmin (Import tab) or:
--   mysql -u USER -p < database/accessable.sql
--
-- On shared hosting where you cannot CREATE DATABASE, delete the two
-- lines under "Database" below and import into the database your host
-- created for you.
--
-- Demo accounts (CHANGE THESE PASSWORDS BEFORE GOING LIVE):
--   Admin: admin@accessable.test / Admin@1234
--   User:  user@accessable.test  / User@1234
--   (reviewer2@ and reviewer3@accessable.test also use User@1234)
--
-- Venues mirror the team's demo venues in js/mock-venues.js (same ids 1-9)
-- and index.php. Feature data is DEMONSTRATION data, not verified records.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Database
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS recently_viewed;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS favourites;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS venue_features;
DROP TABLE IF EXISTS features;
DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- users — accounts for visitors who register, plus administrators
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100)  NOT NULL,
    email          VARCHAR(190)  NOT NULL,
    password_hash  VARCHAR(255)  NOT NULL,
    role           ENUM('user','admin') NOT NULL DEFAULT 'user',
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    last_login_at  DATETIME      NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categories — venue types (restaurant, museum, ...)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL,
    icon        VARCHAR(50)  NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- venues — public places listed on AccessAble
-- ---------------------------------------------------------------------
CREATE TABLE venues (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(150)  NOT NULL,
    slug           VARCHAR(170)  NOT NULL,
    parent_id      INT UNSIGNED  NULL COMMENT 'Parent venue, e.g. Library -> Ashesi University Campus',
    category_id    INT UNSIGNED  NULL,
    description    TEXT          NULL,
    address        VARCHAR(255)  NOT NULL,
    city           VARCHAR(100)  NOT NULL,
    postcode       VARCHAR(20)   NULL,
    latitude       DECIMAL(10,7) NULL,
    longitude      DECIMAL(10,7) NULL,
    phone          VARCHAR(40)   NULL,
    website        VARCHAR(255)  NULL,
    opening_hours  VARCHAR(255)  NULL,
    image          VARCHAR(255)  NULL COMMENT 'Path relative to project root, e.g. uploads/venues/abc.jpg',
    is_featured    TINYINT(1)    NOT NULL DEFAULT 0,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_by     INT UNSIGNED  NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_venues_slug (slug),
    KEY idx_venues_parent (parent_id),
    KEY idx_venues_category (category_id),
    KEY idx_venues_city (city),
    KEY idx_venues_active (is_active),
    CONSTRAINT fk_venues_parent   FOREIGN KEY (parent_id)   REFERENCES venues(id)     ON DELETE SET NULL,
    CONSTRAINT fk_venues_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_venues_creator  FOREIGN KEY (created_by)  REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- features — the 8 scored accessibility features and their weights
-- (weights must total 100; see plan "Weighted Scoring Table")
-- ---------------------------------------------------------------------
CREATE TABLE features (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_key  VARCHAR(50)  NOT NULL COMMENT 'Key used in code and JS filters, e.g. wheelchairEntrance',
    name         VARCHAR(80)  NOT NULL,
    description  VARCHAR(255) NULL,
    weight       TINYINT UNSIGNED NOT NULL,
    icon         VARCHAR(50)  NULL,
    sort_order   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_features_key (feature_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- venue_features — status of each feature at each venue
-- 'available' earns the full weight; 'unavailable' and 'unknown' earn 0
-- ---------------------------------------------------------------------
CREATE TABLE venue_features (
    venue_id    INT UNSIGNED NOT NULL,
    feature_id  INT UNSIGNED NOT NULL,
    status      ENUM('available','unavailable','unknown') NOT NULL DEFAULT 'unknown',
    notes       VARCHAR(255) NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (venue_id, feature_id),
    KEY idx_vf_feature_status (feature_id, status),
    CONSTRAINT fk_vf_venue   FOREIGN KEY (venue_id)   REFERENCES venues(id)   ON DELETE CASCADE,
    CONSTRAINT fk_vf_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- reviews — one review per user per venue (users can edit theirs)
-- status lets admins (Member 5) hide inappropriate reviews
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_id              INT UNSIGNED NOT NULL,
    user_id               INT UNSIGNED NOT NULL,
    rating                TINYINT UNSIGNED NOT NULL COMMENT 'Overall experience 1-5',
    accessibility_rating  TINYINT UNSIGNED NOT NULL COMMENT 'How accessible it felt 1-5',
    title                 VARCHAR(120) NOT NULL,
    comment               TEXT NOT NULL,
    visit_date            DATE NULL,
    status                ENUM('approved','pending','hidden') NOT NULL DEFAULT 'approved',
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reviews_user_venue (user_id, venue_id),
    KEY idx_reviews_venue_status (venue_id, status),
    CONSTRAINT chk_reviews_rating      CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT chk_reviews_acc_rating  CHECK (accessibility_rating BETWEEN 1 AND 5),
    CONSTRAINT fk_reviews_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- favourites — venues a user has saved (Member 5 + Member 4)
-- ---------------------------------------------------------------------
CREATE TABLE favourites (
    user_id     INT UNSIGNED NOT NULL,
    venue_id    INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, venue_id),
    KEY idx_fav_venue (venue_id),
    CONSTRAINT fk_fav_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_fav_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- user_preferences — features a user NEEDS (used for requirement
-- matching on details page and filter defaults on explore page)
-- ---------------------------------------------------------------------
CREATE TABLE user_preferences (
    user_id     INT UNSIGNED NOT NULL,
    feature_id  INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, feature_id),
    CONSTRAINT fk_pref_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_pref_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- recently_viewed — last venues a logged-in user opened (Member 5)
-- ---------------------------------------------------------------------
CREATE TABLE recently_viewed (
    user_id    INT UNSIGNED NOT NULL,
    venue_id   INT UNSIGNED NOT NULL,
    viewed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, venue_id),
    KEY idx_rv_user_time (user_id, viewed_at),
    CONSTRAINT fk_rv_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_rv_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- reports — incorrect-information reports reviewed by admins (Member 5)
-- ---------------------------------------------------------------------
CREATE TABLE reports (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_id     INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NULL,
    reason       ENUM('incorrect_feature','closed','wrong_details','inappropriate_review','other') NOT NULL,
    details      TEXT NULL,
    status       ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at  DATETIME NULL,
    KEY idx_reports_status (status),
    CONSTRAINT fk_reports_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Users ----------------------------------------------------------------
INSERT INTO users (id, full_name, email, password_hash, role) VALUES
(1, 'Site Administrator', 'admin@accessable.test',     '$2y$10$AAqf/pxKoyw5gS4sV/2.kOFQeXe4nqSMZGORGFgtom1MBB2Acbe6C', 'admin'),
(2, 'Demo User',          'user@accessable.test',      '$2y$10$kOnq8YmAtfN/FFGF1zT2qOOJpQtqQyZ/6StzPBc.2Hmnk21mdmY0e', 'user'),
(3, 'Demo Reviewer Two',  'reviewer2@accessable.test', '$2y$10$kOnq8YmAtfN/FFGF1zT2qOOJpQtqQyZ/6StzPBc.2Hmnk21mdmY0e', 'user'),
(4, 'Demo Reviewer Three','reviewer3@accessable.test', '$2y$10$kOnq8YmAtfN/FFGF1zT2qOOJpQtqQyZ/6StzPBc.2Hmnk21mdmY0e', 'user');

-- Categories (match js/mock-venues.js and index.php) ------------------------
INSERT INTO categories (id, name, slug, icon) VALUES
(1, 'Academic',                'academic',   'book'),
(2, 'Dining',                  'dining',     'utensils'),
(3, 'Events',                  'events',     'calendar'),
(4, 'Sports',                  'sports',     'ball'),
(5, 'Residence',               'residence',  'home'),
(6, 'Innovation',              'innovation', 'bulb'),
(7, 'Campus',                  'campus',     'building'),
(8, 'Restaurant',              'restaurant', 'utensils'),
(9, 'Recreation and Heritage', 'heritage',   'landmark');

-- Features: weights from the plan's Weighted Scoring Table (total 100) --------
-- feature_key values match the checkbox values in explore.php / js/filters.js
INSERT INTO features (id, feature_key, name, description, weight, icon, sort_order) VALUES
(1, 'wheelchairEntrance', 'Wheelchair Entrance', 'Step-free main entrance usable by wheelchair users',    20, '♿', 1),
(2, 'ramps',              'Ramps',               'Ramps or step-free routes where levels change',          15, '↗', 2),
(3, 'elevator',           'Elevator',            'Lift access to all public floors',                       15, '↕', 3),
(4, 'accessibleToilet',   'Accessible Toilet',   'Toilet with grab rails and turning space',               15, '🚻', 4),
(5, 'parking',            'Accessible Parking',  'Designated accessible bays close to the entrance',       10, '🅿', 5),
(6, 'wideDoors',          'Wide Doors',          'Doorways wide enough for wheelchairs (about 80 cm+)',    10, '▯', 6),
(7, 'tactileGuidance',    'Tactile Guidance',    'Tactile paving or guidance paths for visually impaired',  8, '◎', 7),
(8, 'brailleSignage',     'Braille Signage',     'Braille on key signs, lifts and room labels',             7, '⠿', 8);

-- Venues -------------------------------------------------------------------------
-- Parent venue first (id 10), then the nine venues from js/mock-venues.js with the same ids.
INSERT INTO venues (id, name, slug, parent_id, category_id, description, address, city, image, is_featured, created_by) VALUES
(10, 'Ashesi University Campus', 'ashesi-university-campus', NULL, 7,
     'Explore campus facilities, learning spaces, student residences and common areas.',
     'Ashesi University', 'Berekuso',
     'https://res.cloudinary.com/bizzaboprod/image/upload/q_auto%2Cf_auto%2Cc_scale%2Cc_crop%2Cg_custom/v1680132964/yaehuu00wwhbvpdec7oc', 1, 1),
(1, 'Library',              'ashesi-library',              10, 1, 'Main campus library with study areas, computer stations and group rooms.', 'Ashesi University', 'Berekuso', NULL, 0, 1),
(2, 'Engineering Building', 'ashesi-engineering-building', 10, 1, 'Engineering classrooms, laboratories and workshops.',                         'Ashesi University', 'Berekuso', NULL, 0, 1),
(3, 'Cafeteria',            'ashesi-cafeteria',            10, 2, 'Main campus dining hall serving students, staff and visitors.',               'Ashesi University', 'Berekuso', NULL, 0, 1),
(4, 'Amphitheatre',         'ashesi-amphitheatre',         10, 3, 'Open-air venue used for events, performances and gatherings.',              'Ashesi University', 'Berekuso', NULL, 0, 1),
(5, 'Sports Field',         'ashesi-sports-field',         10, 4, 'Outdoor sports field for football, athletics and recreation.',               'Ashesi University', 'Berekuso', NULL, 0, 1),
(6, 'On-Campus Housing',    'ashesi-on-campus-housing',    10, 5, 'Student residence halls on the Ashesi campus.',                              'Ashesi University', 'Berekuso', NULL, 0, 1),
(7, 'Off-Campus Housing',   'berekuso-off-campus-housing', NULL, 5, 'Private student hostels in Berekuso township.',                          'Berekuso', 'Berekuso', NULL, 0, 1),
(8, 'STEM Building',        'ashesi-stem-building',        10, 1, 'Science and technology teaching building with lecture halls and labs.',    'Ashesi University', 'Berekuso', NULL, 1, 1),
(9, 'The Hive',             'ashesi-the-hive',             10, 6, 'Innovation and entrepreneurship space for student projects.',               'Ashesi University', 'Berekuso', NULL, 0, 1),
(11, 'Rose Garden Restaurant', 'rose-garden-restaurant', NULL, 8,
     'Explore a community dining space and discover its accessibility facilities.',
     'Accra', 'Accra',
     'https://images.squarespace-cdn.com/content/v1/65cfd1369377d32bcd0051fa/5cd4f62f-c406-46d5-b2e6-e90bbcedd39c/Rose%2BGarden-%2BSheeda%2BTravel%2BTribe.jpg?format=1000w', 1, 1),
(12, 'Kwame Nkrumah Memorial Park', 'kwame-nkrumah-memorial-park', NULL, 9,
     'Discover a historic public attraction, its gardens, museum and visitor facilities.',
     'Greater Accra', 'Accra',
     'https://a.storyblok.com/f/69559/1920x1282/b25afa33da/accra-1947674_1920.jpg', 1, 1);

-- Feature status per venue ---------------------------------------------------------
-- Features 1-6 copy the true/false values in js/mock-venues.js exactly.
-- Tactile guidance (7) and Braille (8) are not in the mock data, so they were set
-- to bring each computed score close to the mock "score" value.
-- Ids: 1 entrance(20) 2 ramps(15) 3 elevator(15) 4 toilet(15) 5 parking(10) 6 doors(10) 7 tactile(8) 8 braille(7)
INSERT INTO venue_features (venue_id, feature_id, status) VALUES
-- 1 Library               → 85  (mock 85)
(1,1,'available'),(1,2,'available'),(1,3,'unavailable'),(1,4,'available'),(1,5,'available'),(1,6,'available'),(1,7,'available'),(1,8,'available'),
-- 2 Engineering Building  → 70  (mock 70)
(2,1,'available'),(2,2,'available'),(2,3,'available'),(2,4,'unavailable'),(2,5,'available'),(2,6,'available'),(2,7,'unavailable'),(2,8,'unavailable'),
-- 3 Cafeteria             → 60  (mock 60)
(3,1,'available'),(3,2,'available'),(3,3,'unavailable'),(3,4,'unavailable'),(3,5,'unavailable'),(3,6,'available'),(3,7,'available'),(3,8,'available'),
-- 4 Amphitheatre          → 40  (mock 45)
(4,1,'unavailable'),(4,2,'available'),(4,3,'unavailable'),(4,4,'unavailable'),(4,5,'available'),(4,6,'unavailable'),(4,7,'available'),(4,8,'available'),
-- 5 Sports Field          → 25  (mock 35)
(5,1,'unavailable'),(5,2,'unavailable'),(5,3,'unavailable'),(5,4,'unavailable'),(5,5,'available'),(5,6,'unavailable'),(5,7,'available'),(5,8,'available'),
-- 6 On-Campus Housing     → 77  (mock 75)
(6,1,'available'),(6,2,'available'),(6,3,'unavailable'),(6,4,'available'),(6,5,'available'),(6,6,'available'),(6,7,'unavailable'),(6,8,'available'),
-- 7 Off-Campus Housing    → 28  (mock 30)
(7,1,'unavailable'),(7,2,'unavailable'),(7,3,'unavailable'),(7,4,'unavailable'),(7,5,'available'),(7,6,'available'),(7,7,'available'),(7,8,'unknown'),
-- 8 STEM Building         → 93  (mock 95)
(8,1,'available'),(8,2,'available'),(8,3,'available'),(8,4,'available'),(8,5,'available'),(8,6,'available'),(8,7,'available'),(8,8,'unknown'),
-- 9 The Hive              → 65  (mock 72)
(9,1,'available'),(9,2,'available'),(9,3,'unavailable'),(9,4,'available'),(9,5,'unavailable'),(9,6,'unavailable'),(9,7,'available'),(9,8,'available'),
-- 10 Ashesi University Campus (whole site) → 85
(10,1,'available'),(10,2,'available'),(10,3,'available'),(10,4,'available'),(10,5,'available'),(10,6,'available'),(10,7,'unknown'),(10,8,'unknown'),
-- 11 Rose Garden Restaurant → 55
(11,1,'available'),(11,2,'available'),(11,3,'unavailable'),(11,4,'unknown'),(11,5,'available'),(11,6,'available'),(11,7,'unavailable'),(11,8,'unavailable'),
-- 12 Kwame Nkrumah Memorial Park → 70
(12,1,'available'),(12,2,'available'),(12,3,'unavailable'),(12,4,'available'),(12,5,'available'),(12,6,'available'),(12,7,'unknown'),(12,8,'unavailable');

-- Sample reviews (demo accounts) — averages sit close to the mock "rating" values -----
INSERT INTO reviews (venue_id, user_id, rating, accessibility_rating, title, comment, visit_date) VALUES
(1, 2, 5, 5, 'Easy to use and well signed',   'Step-free entrance and the accessible toilet is on the ground floor. Braille labels on the study rooms.', '2026-10-14'),
(1, 3, 4, 4, 'Good, but upstairs is hard',    'Everything downstairs is accessible, but there is no lift to the upper reading area.',               '2026-10-21'),
(2, 2, 5, 4, 'Lift makes the labs reachable', 'The lift reaches every lab floor. Only issue is there is no accessible toilet in the building.',       '2026-10-03'),
(2, 3, 4, 3, 'Fine apart from the toilets',   'Wide doors and ramps throughout, but I had to go to another building for an accessible toilet.',      '2026-09-29'),
(2, 4, 4, 4, 'Spacious workshops',            'Workshops have plenty of turning space and the parking is right outside.',                             '2026-10-11'),
(3, 3, 4, 3, 'Good food, tight seating',      'Ramp at the entrance works well, but tables are close together at lunchtime.',                          '2026-10-16'),
(3, 4, 4, 4, 'Easy to get in',                'Wide doors and a gentle ramp. No accessible toilet inside, the nearest is in the library.',           '2026-10-09'),
(4, 2, 5, 3, 'Great events, steps to seats',  'The ramp gets you to the lower level, but most seating is on steps.',                                   '2026-09-26'),
(4, 3, 4, 2, 'Limited seating options',       'Only a few spaces at the front suit wheelchair users. Parking nearby helps.',                           '2026-10-10'),
(4, 4, 4, 3, 'Lovely venue',                  'Nice atmosphere, but the path from the main walkway is uneven after rain.',                             '2026-10-24'),
(5, 3, 4, 2, 'Fun but hard to reach',         'No ramp onto the field and the ground is uneven. Parking is close by.',                                 '2026-10-05'),
(5, 4, 4, 2, 'Spectating is difficult',       'Hard to find a level spot to watch from. Needs a paved viewing area.',                                  '2026-10-18'),
(6, 2, 4, 4, 'Ground-floor rooms work well',  'Accessible rooms on the ground floor with a step-free route from parking.',                             '2026-09-30'),
(6, 4, 4, 3, 'No lift to upper floors',       'Good ground-floor access, but visiting friends upstairs means stairs only.',                            '2026-10-12'),
(7, 3, 4, 2, 'Affordable but not accessible', 'Steps at most entrances and shared bathrooms are small.',                                                '2026-10-02'),
(7, 4, 3, 2, 'Check before you book',         'Some hostels have wide doors, but none I visited had an accessible toilet.',                            '2026-10-19'),
(8, 2, 5, 5, 'Best building on campus',       'Lifts, wide doors, accessible toilets on every floor and tactile paving at the entrance.',             '2026-10-07'),
(8, 3, 5, 5, 'Fully accessible',              'Never had a problem getting around. Parking is right by the entrance.',                                 '2026-10-15'),
(8, 4, 4, 4, 'Very good',                     'Excellent access. Braille signage would make it perfect.',                                              '2026-10-22'),
(9, 2, 5, 4, 'Welcoming space',               'Step-free entry and an accessible toilet, but the doors are a little narrow.',                         '2026-10-08'),
(9, 3, 4, 3, 'Narrow doorways',               'Great space to work, but I needed help with the internal doors.',                                       '2026-10-17'),
(9, 4, 4, 4, 'Good ground floor',             'Everything is on one level, which makes it easy to move around.',                                       '2026-10-23'),
(11, 2, 5, 4, 'Lovely garden seating',        'Step-free route through the garden and parking close to the gate.',                                     '2026-09-20'),
(11, 4, 4, 3, 'Ask about the toilets',        'Getting in is easy, but I could not confirm whether the toilet is accessible.',                        '2026-10-04'),
(12, 2, 4, 4, 'Paved paths throughout',       'Smooth paths around the gardens and an accessible toilet near the entrance.',                          '2026-09-27'),
(12, 3, 4, 3, 'Museum upstairs is harder',    'The grounds are easy to explore, but there is no lift to every part of the museum.',                   '2026-10-13');

-- Sample favourites, needs and recently viewed (Demo User) --------------------------
INSERT INTO favourites (user_id, venue_id) VALUES (2, 1), (2, 8), (2, 11);
INSERT INTO user_preferences (user_id, feature_id) VALUES (2, 1), (2, 4), (2, 5);
INSERT INTO recently_viewed (user_id, venue_id) VALUES (2, 1), (2, 8);
