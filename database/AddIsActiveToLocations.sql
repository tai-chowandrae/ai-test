ALTER TABLE locations
  ADD COLUMN IsActive TINYINT(1) NOT NULL DEFAULT 1 AFTER DefaultTripDescription;
