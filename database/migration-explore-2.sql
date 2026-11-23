USE accessable;

-- Region (for filtering venues across Ghana) and a prototype contact email
ALTER TABLE venues
  ADD COLUMN region VARCHAR(60) NULL AFTER city,
  ADD COLUMN contact_email VARCHAR(255) NULL AFTER website,
  ADD INDEX idx_venues_region (region);

-- Regions for the venues we already have
UPDATE venues SET region = 'Greater Accra' WHERE city = 'Accra';
UPDATE venues SET region = 'Eastern' WHERE city = 'Berekuso';

-- Prototype emails: clearly fake (.example is a reserved, undeliverable domain)
UPDATE venues SET contact_email = CONCAT(slug, '@prototype.accessable.example')
WHERE contact_email IS NULL;

-- Feedback requests sent by users to venues
CREATE TABLE IF NOT EXISTS feedback_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  venue_id INT UNSIGNED NOT NULL,
  requested_features VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  recipient_email VARCHAR(255) NOT NULL,
  status ENUM('recorded', 'replied', 'closed') NOT NULL DEFAULT 'recorded',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fr_user (user_id),
  INDEX idx_fr_venue (venue_id),
  CONSTRAINT fk_fr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fr_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB;