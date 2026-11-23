USE accessable;

-- STEP 1: categories

-- Move venues out of Innovation and Events into Campus
UPDATE venues
SET category_id = (SELECT id FROM categories WHERE slug = 'campus')
WHERE category_id IN (SELECT id FROM categories WHERE slug IN ('innovation', 'events'));

-- Remove the two categories
DELETE FROM categories WHERE slug IN ('innovation', 'events');

-- Recreation and Heritage becomes Tourist Sites (slug stays 'heritage' so no code breaks)
UPDATE categories SET name = 'Tourist Sites' WHERE slug = 'heritage';

-- New categories (safe to run twice)
INSERT IGNORE INTO categories (name, slug, icon) VALUES
  ('Recreation', 'recreation', 'activity'),
  ('Airports', 'airports', 'plane');

-- STEP 2: Academic City campus + Off-Campus Housing under both universities

-- Attach the existing Off-Campus Housing to Ashesi
SET @ashesi := (SELECT id FROM venues WHERE name = 'Ashesi University Campus' LIMIT 1);
UPDATE venues SET parent_id = @ashesi
WHERE name = 'Off-Campus Housing' AND parent_id IS NULL;

-- Academic City campus (parent record)
INSERT IGNORE INTO venues (name, slug, parent_id, category_id, description, address, city, website, is_active)
VALUES ('Academic City University College', 'academic-city-university-college', NULL,
        (SELECT id FROM categories WHERE slug = 'campus'),
        'Private university college in Haatso, Accra.',
        'Property #279-302, Agbogba, Haatso', 'Accra', 'https://acity.edu.gh/', 1);

SET @acity := (SELECT id FROM venues WHERE slug = 'academic-city-university-college' LIMIT 1);

-- Off-Campus Housing for Academic City students
INSERT IGNORE INTO venues (name, slug, parent_id, category_id, description, address, city, is_active)
VALUES ('Off-Campus Housing', 'off-campus-housing-academic-city', @acity,
        (SELECT id FROM categories WHERE slug = 'residence'),
        'Student housing near Academic City.',
        'Near Academic City, Haatso', 'Accra', 1);

-- Give the new venues every feature with status Unknown (nothing is verified)
INSERT IGNORE INTO venue_features (venue_id, feature_id, status)
SELECT v.id, f.id, 'unknown'
FROM venues v CROSS JOIN features f
WHERE v.slug IN ('academic-city-university-college', 'off-campus-housing-academic-city');

-- STEP 3: rename Off-Campus Housing, add Ashesi Gym, add Academic City facilities
SET @ashesi := (SELECT id FROM venues WHERE name = 'Ashesi University Campus' LIMIT 1);
SET @acity  := (SELECT id FROM venues WHERE slug = 'academic-city-university-college' LIMIT 1);

UPDATE venues SET name = 'Ashesi Off-Campus Housing'
WHERE name = 'Off-Campus Housing' AND parent_id = @ashesi;

UPDATE venues SET name = 'Academic City Off-Campus Housing'
WHERE slug = 'off-campus-housing-academic-city';

INSERT IGNORE INTO venues (name, slug, parent_id, category_id, description, address, city, website, is_active) VALUES
('Ashesi Gym', 'ashesi-gym', @ashesi, (SELECT id FROM categories WHERE slug = 'sports'),
 'Gym at the Ashesi sports centre, which also has a football field and running track.',
 '1 University Avenue', 'Berekuso', NULL, 1),
('Academic City Library', 'academic-city-library', @acity, (SELECT id FROM categories WHERE slug = 'academic'),
 'Library at Academic City University College.',
 'Property #279-302, Agbogba, Haatso', 'Accra', 'https://acity.edu.gh/library/', 1),
('Academic City Dining', 'academic-city-dining', @acity, (SELECT id FROM categories WHERE slug = 'dining'),
 'Dining at Academic City University College.',
 'Property #279-302, Agbogba, Haatso', 'Accra', 'https://acity.edu.gh/student-life/dining-meal-plans/', 1),
('Academic City Sports and Recreation', 'academic-city-sports', @acity, (SELECT id FROM categories WHERE slug = 'sports'),
 'Sports and recreation at Academic City University College.',
 'Property #279-302, Agbogba, Haatso', 'Accra', 'https://acity.edu.gh/student-life/', 1),
('Academic City Innovation and Entrepreneurship Centre', 'academic-city-innovation-centre', @acity, (SELECT id FROM categories WHERE slug = 'academic'),
 'Innovation and entrepreneurship centre at Academic City University College.',
 'Property #279-302, Agbogba, Haatso', 'Accra', 'https://tec.acity.edu.gh/', 1);

-- Every feature starts as Unknown for the new venues
INSERT IGNORE INTO venue_features (venue_id, feature_id, status)
SELECT v.id, f.id, 'unknown'
FROM venues v CROSS JOIN features f
WHERE v.slug IN ('ashesi-gym', 'academic-city-library', 'academic-city-dining',
                 'academic-city-sports', 'academic-city-innovation-centre');